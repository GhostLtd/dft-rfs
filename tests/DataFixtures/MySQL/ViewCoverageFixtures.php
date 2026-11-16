<?php

namespace App\Tests\DataFixtures\MySQL;

use App\Entity\Domestic\DriverAvailability;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Feedback;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\International\SurveyNotification as InternationalSurveyNotification;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Entity\RoRo\VehicleCount;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Tests\DataFixtures\Domestic\DaySummaryFixtures;
use App\Tests\DataFixtures\International\ActionExtendedFixtures;
use App\Tests\DataFixtures\PreEnquiry\ResponseFixtures as PreEnquiryResponseFixtures;
use App\Tests\DataFixtures\RoRo\SurveyFixtures as RoRoSurveyFixtures;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Data that gives every view in sql/views/ at least one qualifying row (tests/MySQL/ViewsTest), built on the
 * existing per-area fixtures. Values used in ViewsTest's assertions are set here, next to their constants.
 */
class ViewCoverageFixtures extends Fixture implements DependentFixtureInterface
{
    public const string DOMESTIC_SUBMISSION_DATE = '2021-05-10 12:00:00';
    public const int DRIVER_AVERAGE_BONUS_PENCE = 12345;
    public const string PRE_ENQUIRY_SUBMISSION_DATE = '2021-06-15 09:00:00';
    public const array RORO_COUNTS = ['AT' => 10, 'other' => 2, 'trailers' => 5];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadDomestic($manager);
        $this->loadInternational($manager);
        $this->loadPreEnquiry();
        $this->loadRoRo();

        $manager->flush();
    }

    private function loadDomestic(ObjectManager $manager): void
    {
        $survey = $this->getReference('survey:simple', DomesticSurvey::class);

        // view_export_domestic_driver_availability only includes approved/closed surveys
        $survey
            ->setState(SurveyStateInterface::STATE_APPROVED)
            ->setSubmissionDate(new \DateTime(self::DOMESTIC_SUBMISSION_DATE));

        $driverAvailability = (new DriverAvailability())
            ->setNumberOfDriversEmployed(5)
            ->setHasVacancies('yes')
            // "yes" with no reasons chosen: the view reports NULL rather than []
            ->setReasonsForDriverVacancies([])
            ->setAverageBonus(self::DRIVER_AVERAGE_BONUS_PENCE)
            ->setSurvey($survey);
        $survey->setDriverAvailability($driverAvailability);
        $manager->persist($driverAvailability);

        $feedback = (new Feedback())
            ->setCreatedAt(new \DateTime(self::DOMESTIC_SUBMISSION_DATE))
            ->setExperienceRating('good')
            ->setHasCompletedPaperSurvey(false)
            ->setHadIssues('no')
            ->setComments('View coverage');
        $survey->setFeedback($feedback);
        $manager->persist($feedback);
    }

    private function loadInternational(ObjectManager $manager): void
    {
        $survey = $this->getReference('survey:int:simple', InternationalSurvey::class);

        foreach ([NotificationType::REMINDER_1, NotificationType::REMINDER_2] as $type) {
            $notification = (new InternationalSurveyNotification())
                ->setType($type)
                ->setChannel(NotificationChannel::EMAIL)
                ->setSentAt(new \DateTime('2021-05-20'));
            $survey->addNotification($notification);
            $manager->persist($notification);
        }
    }

    private function loadPreEnquiry(): void
    {
        // view_export_pre_enquiry only includes closed pre-enquiries
        $this->getReference('pre-enquiry:survey', PreEnquiry::class)
            ->setState(SurveyStateInterface::STATE_CLOSED)
            ->setSubmissionDate(new \DateTime(self::PRE_ENQUIRY_SUBMISSION_DATE));
    }

    private function loadRoRo(): void
    {
        // Survey 1 is closed and active (in every RoRo view); survey 2 stays new and inactive (in none)
        $survey = $this->getReference('roro:survey:1', RoRoSurvey::class)
            ->setState(SurveyStateInterface::STATE_CLOSED)
            ->setIsActiveForPeriod(true)
            ->setDataEntryMethod('advanced');

        /** @var VehicleCount $vehicleCount */
        foreach ($survey->getVehicleCounts() as $vehicleCount) {
            $code = $vehicleCount->getCountryCode() ?? $vehicleCount->getOtherCode();
            $vehicleCount->setVehicleCount(self::RORO_COUNTS[$code] ?? null);
        }
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            DayStopFixtures::class,
            DaySummaryFixtures::class,
            ActionExtendedFixtures::class,
            PreEnquiryResponseFixtures::class,
            RoRoSurveyFixtures::class,
        ];
    }
}
