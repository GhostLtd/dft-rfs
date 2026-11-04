<?php

namespace App\Command;

use App\Entity\International\Trip;
use App\Entity\SurveyStateInterface;
use App\Features;
use App\Utility\PostcodeChangeoverHelper;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Pre-enable check for the IRHS postcode changeover (Features::IRHS_POSTCODE_CHANGEOVER +
 * APP_IRHS_POSTCODE_CHANGEOVER_DATE). Edit access isn't limited by survey start date, so surveys at or after a
 * candidate changeover date may already hold trips whose origin/destination would fail the postcode-only forms;
 * this lists them. See docs/ADRs/2026-11-02-irhs-trip-postcodes.md.
 *
 * Read-only: it never persists or flushes.
 */
#[AsCommand('app:irhs:check-postcode-changeover', 'List IRHS trips that would fail postcode-only validation if the IRHS postcode changeover were enabled from a given survey-period start date')]
class CheckIrhsPostcodeChangeoverCommand extends Command
{
    /**
     * States in which a trip can still be edited: SurveyVoter::EDIT (respondents) allows the active states, and
     * AdminSurveyVoter::EDIT (admins) allows those plus closed.
     */
    public const array EDITABLE_STATES = [...SurveyStateInterface::ACTIVE_STATES, SurveyStateInterface::STATE_CLOSED];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface     $validator,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption('date', null, InputOption::VALUE_REQUIRED, 'Candidate changeover date (YYYY-MM-DD): surveys whose period starts on or after it are checked');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dateString = (string) $input->getOption('date');
        $date = \DateTime::createFromFormat('!Y-m-d', $dateString);
        if ($date === false || $date->format('Y-m-d') !== $dateString) {
            $io->error('--date must be a date in YYYY-MM-DD format');
            return Command::INVALID;
        }

        $outwardCodeCheck = Features::isEnabled(Features::OUTWARD_CODE_CHECK);
        $io->text([
            "Surveys with a period start on or after {$dateString}, in states: " . implode(', ', self::EDITABLE_STATES),
            'outward-code-check: ' . ($outwardCodeCheck ? 'enabled' : 'DISABLED (only the postcode format is checked; run with the target environment\'s APP_FEATURES)'),
            // @phpstan-ignore ternary.alwaysTrue (the constant is a product switch; report whichever way it's set)
            'Partial postcodes allowed: ' . (PostcodeChangeoverHelper::IRHS_ALLOW_PARTIAL_POSTCODE ? 'yes' : 'no'),
        ]);

        // ['Default'] is required: Postcode passes groups straight through, so [] would leave it with no groups and
        // a plain validate() would silently skip it.
        $constraint = PostcodeChangeoverHelper::createIrhsPostcodeConstraint(['Default']);

        /** @var iterable<Trip> $trips */
        $trips = $this->entityManager->createQueryBuilder()
            ->select('trip, vehicle, response, survey')
            ->from(Trip::class, 'trip')
            ->join('trip.vehicle', 'vehicle')
            ->join('vehicle.surveyResponse', 'response')
            ->join('response.survey', 'survey')
            ->where('survey.surveyPeriodStart >= :date')
            ->andWhere('survey.state IN (:states)')
            ->orderBy('survey.surveyPeriodStart')
            ->addOrderBy('survey.referenceNumber')
            // survey_period_start is a DATE column: bind as a date, or the boundary day is compared against '... 00:00:00' and missed
            ->setParameter('date', $date, Types::DATE_MUTABLE)
            ->setParameter('states', self::EDITABLE_STATES)
            ->getQuery()
            ->toIterable();

        $rows = [];
        $failingTripIds = [];
        foreach ($trips as $trip) {
            $survey = $trip->getVehicle()->getSurveyResponse()->getSurvey();

            foreach (['origin' => $trip->getOrigin(), 'destination' => $trip->getDestination()] as $field => $value) {
                if ($this->validator->validate($value, $constraint)->count() > 0) {
                    $failingTripIds[$trip->getId()] = true;
                    $rows[] = [
                        $survey->getReferenceNumber(),
                        $survey->getSurveyPeriodStart()?->format('Y-m-d'),
                        $survey->getState(),
                        $trip->getId(),
                        $field,
                        $value,
                    ];
                }
            }
        }

        if (empty($rows)) {
            $io->success('No trips would fail postcode-only validation.');
            return Command::SUCCESS;
        }

        $io->table(['Survey reference', 'Period start', 'State', 'Trip', 'Field', 'Value'], $rows);
        $io->error(sprintf(
            '%d value(s) on %d trip(s) would fail postcode-only validation. Choose a later changeover date, or deal with these trips first.',
            count($rows),
            count($failingTripIds),
        ));

        return Command::FAILURE;
    }
}
