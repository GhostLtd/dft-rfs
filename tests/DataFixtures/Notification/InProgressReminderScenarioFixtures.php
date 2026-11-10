<?php

namespace App\Tests\DataFixtures\Notification;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;
use App\Utility\AuditEntityLogger\SurveyStateLogger;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

/**
 * "Now" for these scenarios is 2026-09-10 09:00 (a Thursday); APP_IN_PROGRESS_REMINDERS_FROM is 2026-08-15.
 * Survey periods end 2026-08-31 unless noted. Surveys are in progress unless noted. Reference names describe
 * the expected outcome. Cutoffs: reminder 1 needs periodEnd <= 2026-09-03 21:00; reminders 2/3 need the previous
 * reminder <= 2026-08-27 21:00; recent contact means after 2026-09-03 09:00.
 */
class InProgressReminderScenarioFixtures extends Fixture
{
    public const string NOW = '2026-09-10 09:00:00';
    public const string FROM = '2026-08-15';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);
        $chased = [];
        $code1 = 14000000;

        $make = function (string $ref, array $notifications = [], ?string $periodEnd = '2026-08-31', string $state = SurveyStateInterface::STATE_IN_PROGRESS, ?string $telephone = null, ?string $invitationEmails = 'inv@example.com') use ($manager, &$code1): Survey {
            $survey = (new Survey())
                ->setSurveyPeriodStart(new \DateTime('2026-08-01'))
                ->setSurveyPeriodEnd($periodEnd === null ? null : new \DateTime($periodEnd))
                ->setIsNorthernIreland(false)
                ->setRegistrationMark(strtoupper(substr(md5($ref), 0, 7)))
                ->setPasscodeUser((new PasscodeUser())->setUsername((string) ++$code1)->setPlainPassword('test'))
                ->setState($state);
            if ($invitationEmails !== null) {
                $survey->setInvitationEmails($invitationEmails);
            }
            if ($telephone !== null) {
                $survey->setResponse((new SurveyResponse())->setContactTelephone($telephone));
            }

            foreach ($notifications as [$type, $sentAt]) {
                $n = (new SurveyNotification())->setType($type)->setChannel(NotificationChannel::EMAIL)->setSentAt(new \DateTime($sentAt));
                $survey->addNotification($n);
                $manager->persist($n);
            }

            $manager->persist($survey);
            $this->addReference($ref, $survey);
            return $survey;
        };

        $ip1 = NotificationType::IN_PROGRESS_REMINDER_1;
        $ip2 = NotificationType::IN_PROGRESS_REMINDER_2;
        $ip3 = NotificationType::IN_PROGRESS_REMINDER_3;

        // reminder 1
        $make('ip1:due', telephone: '07700 900001');
        $make('ip1:period-end-tolerance', periodEnd: '2026-09-03');
        $make('ip1:period-end-too-recent', periodEnd: '2026-09-04');
        $make('ip1:period-end-on-from', periodEnd: '2026-08-15');
        $make('ip1:period-end-before-from', periodEnd: '2026-08-14');
        $make('ip1:no-period-end', periodEnd: null);
        // Maximum span: periods ending more than 56 days before NOW (i.e. before 2026-07-16) get no reminders. These
        // are also before FROM, so testMaximumSpan uses an earlier FROM.
        $make('ip1:period-end-oldest-allowed', periodEnd: '2026-07-16');
        $make('ip1:period-end-too-old', periodEnd: '2026-07-15');
        $make('ip1:wrong-state', state: SurveyStateInterface::STATE_INVITATION_SENT);
        $make('ip1:already-sent', [[$ip1, '2026-09-01 09:00']]);
        $make('ip1:no-contact', invitationEmails: null);
        $make('ip1:manual-reminder-recently', [[NotificationType::MANUAL_REMINDER, '2026-09-06 10:00']]);
        $chased['ip1:chased-recently'] = [$make('ip1:chased-recently'), '2026-09-05 10:00', true];
        $chased['ip1:chased-long-ago'] = [$make('ip1:chased-long-ago'), '2026-09-01 10:00', true];
        $chased['ip1:unchased-note-recent'] = [$make('ip1:unchased-note-recent'), '2026-09-08 10:00', false];
        $startedRecently = $make('ip1:started-recently');

        // reminder 2
        $make('ip2:due', [[$ip1, '2026-08-26 10:00']]);
        $make('ip2:tolerance', [[$ip1, '2026-08-27 20:00']]);
        $make('ip2:too-recent', [[$ip1, '2026-08-28 10:00']]);
        $make('ip2:already-sent', [[$ip1, '2026-08-01 10:00'], [$ip2, '2026-09-01 10:00']]);
        $make('ip2:closed', [[$ip1, '2026-08-20 10:00']], state: SurveyStateInterface::STATE_CLOSED);
        $chased['ip2:chased-recently'] = [$make('ip2:chased-recently', [[$ip1, '2026-08-20 10:00']]), '2026-09-05 10:00', true];
        $make('ip2:period-end-too-old', [[$ip1, '2026-08-26 10:00']], periodEnd: '2026-07-15');

        // reminder 3
        $make('ip3:due', [[$ip1, '2026-07-20 10:00'], [$ip2, '2026-08-20 10:00']]);
        $make('ip3:already-sent', [[$ip1, '2026-07-01 10:00'], [$ip2, '2026-07-15 10:00'], [$ip3, '2026-07-29 10:00']]);
        $make('ip3:period-end-too-old', [[$ip1, '2026-07-20 10:00'], [$ip2, '2026-08-20 10:00']], periodEnd: '2026-07-15');
        $make('ip3:period-end-oldest-allowed', [[$ip1, '2026-07-20 10:00'], [$ip2, '2026-08-20 10:00']], periodEnd: '2026-07-16');

        $manager->flush();

        $manager->persist((new AuditLog())
            ->setCategory(SurveyStateLogger::CATEGORY)
            ->setUsername('fixtures')
            ->setEntityClass(Survey::class)
            ->setEntityId($startedRecently->getId())
            ->setTimestamp(new \DateTime('2026-09-07 10:00'))
            ->setData(['from' => SurveyStateInterface::STATE_INVITATION_SENT, 'to' => SurveyStateInterface::STATE_IN_PROGRESS]));
        $manager->flush();

        // Notes are inserted directly: NotePrePersistSubscriber overwrites createdAt with the wall clock and needs a
        // logged-in user.
        foreach ($chased as [$survey, $createdAt, $wasChased]) {
            $manager->getConnection()->insert('domestic_survey_note', [
                'id' => (string) Uuid::v4(),
                'survey_id' => $survey->getId(),
                'note' => 'fixture note',
                'created_by' => 'fixtures',
                'created_at' => $createdAt,
                'was_chased' => $wasChased ? 1 : 0,
            ]);
        }
    }
}
