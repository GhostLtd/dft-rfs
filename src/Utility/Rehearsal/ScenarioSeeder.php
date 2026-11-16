<?php

namespace App\Utility\Rehearsal;

use App\DTO\RoRo\OperatorRoute;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Domestic\SurveyResponse as DomesticResponse;
use App\Entity\Embeddable\LongAddress;
use App\Entity\International\Company;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Entity\RoRo\SurveyNotification as RoRoNotification;
use App\Entity\Route\Route;
use App\Entity\RoRoUser;
use App\Entity\SurveyNotificationsInterface;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\SmsAllowance;
use App\Utility\Reminder\DisabledRemindersHelper;
use App\Utility\Reminder\DomesticInProgressReminders;
use App\Utility\RoRo\SurveyCreationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Creates the rehearsal surveys, notification history, RoRo users and seed manifest in one transaction.
 * Invite scenarios also queue their invitations through the usual lifecycle listeners.
 */
class ScenarioSeeder
{
    public const string RORO_OPERATOR = 'SEED reminder operator';
    public const int RORO_OPERATOR_CODE = 9999;
    public const string RORO_USER = 'seed-roro@example.invalid';
    private const string LINE_2 = '1 Seed Street';
    private const string POSTCODE = 'BA1 1HR';
    private const string LONDON = 'Europe/London';

    /** @var list<object> notification rows created as scenario history (recorded in the manifest) */
    private array $historyRows = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SeedManifestStore $manifestStore,
        private readonly SurveyCreationHelper $roroSurveyCreationHelper,
        private readonly DomesticInProgressReminders $inProgressReminders,
        private readonly DisabledRemindersHelper $disabledRemindersHelper,
        private readonly SmsAllowance $smsAllowance,
    ) {}

    /**
     * Returns the configured reminder start date, disabled families and effective annual SMS cap.
     *
     * @return array{from: string, disabled: list<string>, smsCap: int}
     */
    public function getSettings(): array
    {
        return [
            'from' => (string) $this->inProgressReminders->getRemindersFromSetting(),
            'disabled' => $this->disabledRemindersHelper->getDisabled(),
            'smsCap' => $this->smsAllowance->effectiveCap(),
        ];
    }

    /**
     * Returns why seeding cannot run on this date with these settings, or null when allowed.
     *
     * @param array{from: string, disabled: list<string>, smsCap: int} $settings
     */
    public function getRefusalReason(\DateTimeImmutable $now, array $settings): ?string
    {
        $london = $now->setTimezone(new \DateTimeZone(self::LONDON));
        if ((int) $london->format('N') >= 6) {
            return 'It is the weekend (Europe/London): in-progress reminders never run at weekends. Seed on a weekday.';
        }
        // RoRo scenarios are for last month, and --report accepts a seed for 24h: seeding before the last day keeps both in one month.
        if ($london->format('j') === $london->format('t')) {
            return 'It is the last day of the month: RoRo reminders look at the previous month, which changes at midnight. Seed tomorrow.';
        }

        $from = \DateTimeImmutable::createFromFormat('!Y-m-d', $settings['from'], new \DateTimeZone(self::LONDON));
        if ($from === false || $from->format('Y-m-d') !== $settings['from']) {
            return "APP_IN_PROGRESS_REMINDERS_FROM is unset or invalid ('{$settings['from']}'). Set it (Y-m-d, at least 10 days ago).";
        }
        if ($from > $london->setTime(0, 0)->sub(new \DateInterval('P10D'))) {
            return "APP_IN_PROGRESS_REMINDERS_FROM ({$settings['from']}) must be at least 10 days before today.";
        }

        return null;
    }

    /**
     * Creates every scenario and stores a manifest of the rows created. Returns that manifest.
     * All writes and queued invitations roll back if any scenario fails.
     *
     * @param array{from: string, disabled: list<string>, smsCap: int} $settings
     */
    public function seed(\DateTimeImmutable $now, array $settings): SeedManifest
    {
        return $this->entityManager->wrapInTransaction(function () use ($now, $settings): SeedManifest {
            $london = $now->setTimezone(new \DateTimeZone(self::LONDON));
            $from = new \DateTimeImmutable($settings['from'], new \DateTimeZone(self::LONDON));
            $scenarios = [];
            $this->historyRows = [];

            $roroUserId = $this->seedRoRo($london, $scenarios);

            $bare = [];
            foreach (ScenarioCatalogue::all() as $scenario) {
                match ($scenario->family) {
                    Scenario::CSRGT, Scenario::IN_PROGRESS => $scenarios[$scenario->name] = $this->seedDomesticSurvey($scenario, $london, $from),
                    Scenario::IRHS => $scenarios[$scenario->name] = $this->seedInternationalSurvey($scenario, $london),
                    Scenario::PRE => $scenario->invite
                        ? $scenarios[$scenario->name] = $this->seedPreEnquiryInvite($scenario)
                        : $bare[$scenario->name] = $this->createUninvitedPreEnquiry($scenario),
                    Scenario::RORO => null, // seeded by seedRoRo()
                    default => throw new \LogicException("Unknown scenario family {$scenario->family}"),
                };
            }
            $this->entityManager->flush();

            // Phase 2 for pre-enquiries: set state, address and history in an UPDATE-only flush.
            foreach ($bare as $name => $preEnquiry) {
                $scenario = ScenarioCatalogue::byName($name);
                $preEnquiry->setState($scenario->state)->setInvitationAddress($this->buildAddress($scenario));
                $this->recordNotificationHistory($preEnquiry, $scenario, $london);
                $scenarios[$name] = ['class' => PreEnquiry::class, 'id' => (string) $preEnquiry->getId()];
            }
            $this->entityManager->flush();

            foreach (ScenarioCatalogue::all() as $scenario) {
                if ($scenario->chasedDaysAgo !== null) {
                    $this->entityManager->getConnection()->insert('domestic_survey_note', [
                        'id' => (string) Uuid::v4(),
                        'survey_id' => $scenarios[$scenario->name]['id'],
                        'note' => 'Seeded chased note',
                        'created_by' => 'seed',
                        'created_at' => $london->sub(new \DateInterval("P{$scenario->chasedDaysAgo}D"))->format('Y-m-d H:i:s'),
                        'was_chased' => 1,
                    ]);
                }
            }

            $ordered = [];
            foreach (ScenarioCatalogue::all() as $scenario) {
                $ordered[$scenario->name] = $scenarios[$scenario->name];
            }

            $historyRowIds = array_map(fn($row) => (string) $row->getId(), $this->historyRows);
            $manifest = new SeedManifest($london, $settings, $ordered, $roroUserId, $historyRowIds);
            $this->manifestStore->write($manifest);
            $this->entityManager->flush();

            return $manifest;
        });
    }

    /**
     * Creates the seeded operator, user and surveys, adds survey entries to $scenarios, and returns the user's ID.
     *
     * @param array<string, array{class: class-string, id: string}> $scenarios
     */
    private function seedRoRo(\DateTimeImmutable $london, array &$scenarios): string
    {
        /** @var list<Route> $routes */
        $routes = $this->entityManager->createQueryBuilder()->select('r')->from(Route::class, 'r')
            ->where('r.isActive = true')->orderBy('r.id')->setMaxResults(2)->getQuery()->getResult();
        if (count($routes) < 2) {
            throw new SeedRefusedException('The database needs at least two active routes (from the migrations) for the RoRo scenarios; found '.count($routes).'. Recreate it with doctrine:migrations:migrate.');
        }

        $operator = (new Operator())->setName(self::RORO_OPERATOR)->setCode(self::RORO_OPERATOR_CODE)->setIsActive(true);
        foreach ($routes as $route) {
            $operator->addRoute($route);
        }
        $user = (new RoRoUser())->setUsername(self::RORO_USER);
        $operator->addUser($user); // keeps both sides in step (setOperator() alone leaves $operator->getUsers() stale)
        $this->entityManager->persist($operator);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $periodStart = \DateTime::createFromImmutable($london->modify('first day of last month')->setTime(0, 0));
        foreach ([['roro:r1-due', $routes[0]], ['roro:r2-due', $routes[1]]] as [$name, $route]) {
            $scenario = ScenarioCatalogue::byName($name);
            $survey = $this->roroSurveyCreationHelper->createSurvey(clone $periodStart, new OperatorRoute($operator->getId(), $route->getId()));
            $survey->setState($scenario->state);
            $this->entityManager->persist($survey);
            foreach ($scenario->history as [$type, $daysAgo]) {
                $row = (new RoRoNotification())->setType($type)->setChannel(NotificationChannel::EMAIL)->setSentAt($this->daysAgo($london, $daysAgo));
                $survey->addNotification($row);
                $this->entityManager->persist($row);
                $this->historyRows[] = $row;
            }
            $this->entityManager->flush();
            $scenarios[$name] = ['class' => RoRoSurvey::class, 'id' => (string) $survey->getId()];
        }

        return (string) $user->getId();
    }

    /**
     * Creates and flushes a CSRGT scenario, returning its survey class and ID for the manifest.
     *
     * @return array{class: class-string, id: string}
     */
    private function seedDomesticSurvey(Scenario $scenario, \DateTimeImmutable $london, \DateTimeImmutable $from): array
    {
        $end = $this->scenarioPeriodEnd($scenario, $london, $from);
        $survey = (new DomesticSurvey())
            ->setRegistrationMark($scenario->registrationMark)
            ->setIsNorthernIreland(false)
            ->setSurveyPeriodStart((clone $end)->modify('-6 days'))
            ->setSurveyPeriodEnd($end);
        if ($scenario->state !== null) {
            $survey->setState($scenario->state);
        }
        if ($scenario->address) {
            $survey->setInvitationAddress($this->buildAddress($scenario));
        }
        if ($scenario->invitationEmail) {
            $survey->setInvitationEmails($scenario->email());
        }
        if ($scenario->contactEmail || $scenario->telephone !== null) {
            $survey->setResponse((new DomesticResponse())
                ->setContactEmail($scenario->contactEmail ? $scenario->email() : null)
                ->setContactTelephone($scenario->telephone));
        }
        $this->recordNotificationHistory($survey, $scenario, $london);
        $this->entityManager->persist($survey);
        $this->entityManager->flush(); // the invite scenario's postPersist queues its invite here

        return ['class' => DomesticSurvey::class, 'id' => (string) $survey->getId()];
    }

    /**
     * Creates and flushes an IRHS survey and its company, returning the survey's class and ID for the manifest.
     *
     * @return array{class: class-string, id: string}
     */
    private function seedInternationalSurvey(Scenario $scenario, \DateTimeImmutable $london): array
    {
        $end = $this->scenarioPeriodEnd($scenario, $london, $london);
        $company = (new Company())->setBusinessName("SEED-{$scenario->name}");
        $survey = (new InternationalSurvey())
            ->setCompany($company)
            ->setReferenceNumber((string) $scenario->reference)
            ->setSurveyPeriodStart((clone $end)->modify('-6 days'))
            ->setSurveyPeriodEnd($end);
        if ($scenario->state !== null) {
            $survey->setState($scenario->state);
        }
        if ($scenario->address) {
            $survey->setInvitationAddress($this->buildAddress($scenario));
        }
        if ($scenario->invitationEmail) {
            $survey->setInvitationEmails($scenario->email());
        }
        $this->recordNotificationHistory($survey, $scenario, $london);
        $this->entityManager->persist($company);
        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        return ['class' => InternationalSurvey::class, 'id' => (string) $survey->getId()];
    }

    /**
     * Creates and flushes a pre-enquiry whose lifecycle listener queues its invite.
     * Returns its survey class and ID for the manifest.
     *
     * @return array{class: class-string, id: string}
     */
    private function seedPreEnquiryInvite(Scenario $scenario): array
    {
        $preEnquiry = (new PreEnquiry())
            ->setReferenceNumber((string) $scenario->reference)
            ->setCompanyName("SEED-{$scenario->name}")
            ->setInvitationAddress($this->buildAddress($scenario));
        $this->entityManager->persist($preEnquiry);
        $this->entityManager->flush();

        return ['class' => PreEnquiry::class, 'id' => (string) $preEnquiry->getId()];
    }

    /**
     * Creates and persists a pre-enquiry without recipients, preventing an automatic invite.
     * Returns the entity so its reminder state and history can be set after the first flush.
     */
    private function createUninvitedPreEnquiry(Scenario $scenario): PreEnquiry
    {
        // No address or emails yet, so the lifecycle listener's invite_user guard blocks the auto-invite.
        $preEnquiry = (new PreEnquiry())
            ->setReferenceNumber((string) $scenario->reference)
            ->setCompanyName("SEED-{$scenario->name}");
        $this->entityManager->persist($preEnquiry);
        return $preEnquiry;
    }

    /**
     * Creates and persists the scenario's previous notification rows and adds them to the manifest history.
     */
    private function recordNotificationHistory(SurveyNotificationsInterface $survey, Scenario $scenario, \DateTimeImmutable $london): void
    {
        foreach ($scenario->history as [$type, $daysAgo]) {
            $row = $survey->createNotification()
                ->setType($type)
                ->setChannel(match (true) {
                    $survey instanceof PreEnquiry => NotificationChannel::LETTER,
                    default => NotificationChannel::EMAIL,
                })
                ->setSentAt($this->daysAgo($london, $daysAgo));
            $survey->addNotification($row);
            $this->entityManager->persist($row);
            $this->historyRows[] = $row;
        }
    }

    /**
     * Returns the scenario's survey end date, relative to today or the configured reminder start date.
     */
    private function scenarioPeriodEnd(Scenario $scenario, \DateTimeImmutable $london, \DateTimeImmutable $from): \DateTime
    {
        $date = $scenario->periodEndAtFrom !== null
            ? $from->modify("{$scenario->periodEndAtFrom} days")
            : $london->modify(($scenario->periodEndDays ?? 14).' days');

        return new \DateTime($date->format('Y-m-d'));
    }

    /**
     * Returns the sample postal address labelled with the scenario name.
     */
    private function buildAddress(Scenario $scenario): LongAddress
    {
        return (new LongAddress())->setLine1($scenario->addressLine1())->setLine2(self::LINE_2)->setPostcode(self::POSTCODE);
    }

    private function daysAgo(\DateTimeImmutable $london, int $days): \DateTime
    {
        return \DateTime::createFromImmutable($london->sub(new \DateInterval("P{$days}D")));
    }
}
