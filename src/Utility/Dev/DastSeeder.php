<?php

namespace App\Utility\Dev;

use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoType;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Domestic\SurveyResponse as DomesticSurveyResponse;
use App\Entity\Domestic\Vehicle as DomesticVehicle;
use App\Entity\Embeddable\Distance;
use App\Entity\Embeddable\LongAddress;
use App\Entity\HazardousGoods;
use App\Entity\International\Action;
use App\Entity\International\Company;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\International\SurveyResponse as InternationalSurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle as InternationalVehicle;
use App\Entity\PasscodeUser;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Entity\SurveyResponse;
use App\Entity\SurveyStateInterface;
use App\Entity\Vehicle;
use App\Utility\PasscodeGenerator;
use App\Workflow\DomesticSurvey\DaySummaryState;
use App\Workflow\InternationalSurvey\TripState;
use App\Workflow\PreEnquiry\PreEnquiryState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Seeds an empty database with one passcode user per survey type, each with a part-completed survey, for the DAST
 * stack (bin/dast, docs/Dast.md). The data follows tests/DataFixtures (see the plan for which fixtures), but with
 * fixed marker strings that the ZAP plans look for to prove a page rendered the seeded data.
 */
class DastSeeder
{
    // 8 digits, as passcode login requires
    public const array USERNAMES = [
        'domestic' => '90000001',
        'international' => '90000002',
        'pre-enquiry' => '90000003',
    ];

    // Plain letters, digits and spaces, so the ZAP plans can use them as literal regexes. Copied into bin/dast.
    public const array MARKERS = [
        'domestic-stops' => 'Dastford',
        'domestic-summary' => 'Zapton',
        'international-trip' => 'Scanville',
        'pre-enquiry' => 'Dast Seed Haulage Ltd',
        'admin-survey' => 'DA57 ZAP',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PasscodeGenerator $passcodeGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, string> KEY => value, in the order bin/dast documents
     */
    public function seed(): array
    {
        if ($this->entityManager->getRepository(PasscodeUser::class)->count([]) > 0) {
            throw new \RuntimeException('The DAST seeder only runs against an empty database (passcode users exist)');
        }

        [$domesticUser, $domesticSurvey] = $this->seedDomestic();
        [$internationalUser, $trip] = $this->seedInternational();
        $preEnquiryUser = $this->seedPreEnquiry();

        return [
            'DAST_DOMESTIC_USERNAME' => $domesticUser->getUsername(),
            'DAST_DOMESTIC_PASSWORD' => $this->passcodeGenerator->getPasswordForUser($domesticUser),
            'DAST_INTERNATIONAL_USERNAME' => $internationalUser->getUsername(),
            'DAST_INTERNATIONAL_PASSWORD' => $this->passcodeGenerator->getPasswordForUser($internationalUser),
            'DAST_PRE_ENQUIRY_USERNAME' => $preEnquiryUser->getUsername(),
            'DAST_PRE_ENQUIRY_PASSWORD' => $this->passcodeGenerator->getPasswordForUser($preEnquiryUser),
            'DAST_PATH_DOMESTIC_STOPS' => $this->path('app_domesticsurvey_day_view', ['dayNumber' => 1]),
            'DAST_PATH_DOMESTIC_SUMMARY' => $this->path('app_domesticsurvey_day_view', ['dayNumber' => 2]),
            'DAST_PATH_INTERNATIONAL_TRIP' => $this->path('app_internationalsurvey_trip_view', ['id' => $trip->getId()]),
            'DAST_PATH_PRE_ENQUIRY_SENTINEL' => $this->path('app_pre_enquiry_wizard', ['state' => PreEnquiryState::STATE_HGV_DETAILS]),
            'DAST_PATH_ADMIN_SURVEY' => $this->path('admin_domestic_survey_view', ['surveyId' => $domesticSurvey->getId()]),
            // Live checks: leaf pages showing the markers (see DastSeederTest::testPagesShowMarkers for why leaves)
            'DAST_CHECK_DOMESTIC_STOPS' => $this->path('app_domesticsurvey_daystop_reorder', ['dayNumber' => 1]),
            'DAST_CHECK_DOMESTIC_SUMMARY' => $this->path('app_domesticsurvey_daysummary_wizard', ['dayNumber' => 2, 'state' => DaySummaryState::STATE_ORIGIN]),
            'DAST_CHECK_INTERNATIONAL_TRIP' => $this->path('app_internationalsurvey_trip_edit_wizard', ['tripId' => $trip->getId(), 'state' => TripState::STATE_ORIGIN_AND_DESTINATION]),
            'DAST_CHECK_PRE_ENQUIRY' => $this->path('app_pre_enquiry_wizard', ['state' => PreEnquiryState::STATE_COMPANY_NAME]),
            'DAST_CHECK_ADMIN_SURVEY' => $this->path('admin_domestic_survey_reveal_passcode', ['surveyId' => $domesticSurvey->getId()]),
        ];
    }

    /** @return array{PasscodeUser, DomesticSurvey} */
    private function seedDomestic(): array
    {
        $user = (new PasscodeUser())->setUsername(self::USERNAMES['domestic']);

        $survey = (new DomesticSurvey())
            ->setSurveyPeriodStart(new \DateTime('2021-05-01'))
            ->setSurveyPeriodEnd(new \DateTime('2021-05-10'))
            ->setIsNorthernIreland(true)
            ->setRegistrationMark(self::MARKERS['admin-survey'])
            ->setPasscodeUser($user)
            ->setInvitationAddress(new LongAddress())
            ->setState(SurveyStateInterface::STATE_IN_PROGRESS);

        $response = (new DomesticSurveyResponse())
            ->setIsInPossessionOfVehicle(DomesticSurveyResponse::IN_POSSESSION_YES)
            ->setContactEmail('dast@example.com')
            ->setContactName('Dast Tester')
            ->setIsExemptVehicleType(false)
            ->setNumberOfEmployees(SurveyResponse::EMPLOYEES_10_TO_49)
            ->setBusinessNature('Haulage');
        $survey->setResponse($response);

        // In the same flush as the response: DataConsistencyListener reads the response's vehicle
        $vehicle = (new DomesticVehicle())
            ->setRegistrationMark($survey->getRegistrationMark())
            ->setAxleConfiguration(232)
            ->setTrailerConfiguration(300)
            ->setBodyType(Vehicle::BODY_TYPE_CURTAIN_SIDED)
            ->setGrossWeight(34000)
            ->setCarryingCapacity(26000)
            ->setOperationType(Vehicle::OPERATION_TYPE_ON_OWN_ACCOUNT);
        $response->setVehicle($vehicle);
        $vehicle->setResponse($response);

        $this->entityManager->persist($user);
        $this->entityManager->persist($survey);
        $this->entityManager->persist($response);
        $this->entityManager->persist($vehicle);
        $this->entityManager->flush();

        $stopsDay = (new Day())
            ->setNumber(1)
            ->setResponse($response)
            ->setHasMoreThanFiveStops(false);
        $this->entityManager->persist($stopsDay);
        $this->entityManager->persist($this->dayStop($stopsDay, 1, self::MARKERS['domestic-stops'], 'Chichester', 'Bananas'));
        $this->entityManager->persist($this->dayStop($stopsDay, 2, 'Worthing', self::MARKERS['domestic-stops'], 'Oranges'));

        $summaryDay = (new Day())
            ->setNumber(2)
            ->setResponse($response)
            ->setHasMoreThanFiveStops(true);
        $summary = (new DaySummary())
            ->setDay($summaryDay)
            ->setOriginLocation(self::MARKERS['domestic-summary'])
            ->setGoodsLoaded(true)
            ->setGoodsTransferredFrom(Day::TRANSFERRED_NONE)
            ->setDestinationLocation('Chichester')
            ->setGoodsUnloaded(true)
            ->setGoodsTransferredTo(Day::TRANSFERRED_NONE)
            ->setFurthestStop(self::MARKERS['domestic-summary'])
            ->setBorderCrossingLocation(null)
            ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER)
            ->setGoodsDescriptionOther('Bananas')
            ->setCargoTypeCode(CargoType::CODE_RC_ROLL_CAGES)
            ->setWeightOfGoodsLoaded(2000)
            ->setWeightOfGoodsUnloaded(1800)
            ->setDistanceTravelledLoaded((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(20))
            ->setDistanceTravelledUnloaded((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(15))
            ->setHazardousGoodsCode(HazardousGoods::CODE_0_NOT_HAZARDOUS)
            ->setNumberOfStopsLoading(1)
            ->setNumberOfStopsUnloading(4)
            ->setNumberOfStopsLoadingAndUnloading(0);
        $this->entityManager->persist($summaryDay);
        $this->entityManager->persist($summary);
        $this->entityManager->flush();

        return [$user, $survey];
    }

    private function dayStop(Day $day, int $number, string $origin, string $destination, string $goods): DayStop
    {
        return (new DayStop())
            ->setNumber($number)
            ->setDay($day)
            ->setBorderCrossingLocation(null)
            ->setCargoTypeCode(CargoType::CODE_RC_ROLL_CAGES)
            ->setOriginLocation($origin)
            ->setGoodsLoaded(true)
            ->setGoodsTransferredFrom(Day::TRANSFERRED_NONE)
            ->setDestinationLocation($destination)
            ->setGoodsUnloaded(true)
            ->setGoodsTransferredTo(Day::TRANSFERRED_NONE)
            ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER)
            ->setGoodsDescriptionOther($goods)
            ->setWeightOfGoodsCarried(800)
            ->setDistanceTravelled((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(20))
            ->setHazardousGoodsCode(HazardousGoods::CODE_0_NOT_HAZARDOUS)
            ->setWasAtCapacity(false)
            ->setWasLimitedBySpace(false)
            ->setWasLimitedByWeight(false);
    }

    /** @return array{PasscodeUser, Trip} */
    private function seedInternational(): array
    {
        $user = (new PasscodeUser())->setUsername(self::USERNAMES['international']);
        $company = (new Company())->setBusinessName('Dast International Ltd');

        $survey = (new InternationalSurvey())
            ->setSurveyPeriodStart(new \DateTime('2021-05-01'))
            ->setSurveyPeriodEnd(new \DateTime('2021-05-10'))
            ->setPasscodeUser($user)
            ->setState(SurveyStateInterface::STATE_IN_PROGRESS)
            ->setCompany($company)
            ->setInvitationEmails('dast-international@example.com')
            ->setInvitationAddress(new LongAddress())
            ->setReferenceNumber('dast-international');

        $response = (new InternationalSurveyResponse())
            ->setContactName('Dast Tester')
            ->setContactEmail('dast@example.com')
            ->setContactTelephone('0800 8118181')
            ->setNumberOfEmployees(SurveyResponse::EMPLOYEES_10_TO_49)
            ->setActivityStatus(InternationalSurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE)
            ->setBusinessNature('Haulage')
            ->setAnnualInternationalHGVJourneyCount(150)
            ->setAnnualInternationalLGVJourneyCount(0)
            ->setInitialDetailsSignedOff(true);
        $survey
            ->setResponse($response)
            ->setResponseStartDate(new \DateTime());

        $vehicle = (new InternationalVehicle())
            ->setRegistrationMark('AA01 ABC')
            ->setAxleConfiguration(333)
            ->setTrailerConfiguration(300)
            ->setBodyType(Vehicle::BODY_TYPE_CURTAIN_SIDED)
            ->setCarryingCapacity(35000)
            ->setGrossWeight(28000)
            ->setOperationType(Vehicle::OPERATION_TYPE_ON_OWN_ACCOUNT);
        $response->addVehicle($vehicle);

        $trip = (new Trip())
            ->setVehicle($vehicle)
            ->setOutboundDate(new \DateTime('2021-05-01'))
            ->setReturnDate(new \DateTime('2021-05-03'))
            ->setIsChangedBodyType(false)
            ->setIsSwappedTrailer(false)
            ->setOutboundUkPort('Dover')
            ->setOutboundForeignPort('Calais')
            ->setReturnForeignPort('Calais')
            ->setReturnUkPort('Dover')
            ->setRoundTripDistance((new Distance())->setUnit(Distance::UNIT_MILES)->setValue('1450.0'))
            ->setCountriesTransitted(['FR', 'BE', 'NL', 'DE'])
            ->setReturnWasEmpty(false)
            ->setReturnWasLimitedBySpace(false)
            ->setReturnWasLimitedByWeight(false)
            ->setOutboundWasEmpty(false)
            ->setOutboundWasLimitedBySpace(false)
            ->setOutboundWasLimitedByWeight(false)
            ->setOrigin(self::MARKERS['international-trip'])
            ->setDestination(self::MARKERS['international-trip']);
        $vehicle->addTrip($trip);

        $loading = (new Action())
            ->setLoading(true)
            ->setCountry('GB')
            ->setName('Chichester')
            ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER)
            ->setGoodsDescriptionOther('Waste Chemicals')
            ->setWeightOfGoods(23000)
            ->setHazardousGoodsCode(HazardousGoods::CODE_8_CORROSIVE_SUBSTANCES)
            ->setCargoTypeCode(CargoType::CODE_LB_LIQUID_BULK);
        $trip->addAction($loading);

        $unloading = (new Action())
            ->setLoading(false)
            ->setCountry('DE')
            ->setName('Duisburg')
            ->setWeightUnloadedAll(true)
            ->setLoadingAction($loading);
        $trip->addAction($unloading);

        foreach ([$user, $company, $survey, $response, $vehicle, $trip, $loading, $unloading] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        return [$user, $trip];
    }

    private function seedPreEnquiry(): PasscodeUser
    {
        $user = (new PasscodeUser())->setUsername(self::USERNAMES['pre-enquiry']);

        $preEnquiry = (new PreEnquiry())
            ->setPasscodeUser($user)
            ->setState(SurveyStateInterface::STATE_IN_PROGRESS)
            ->setReferenceNumber('dast-pre-enquiry')
            ->setInvitationAddress($this->address('Dast Invited Ltd', 'DA10 1AB'))
            ->setInvitationEmails('dast-pre-enquiry@example.com')
            ->setDispatchDate(new \DateTime('2021-06-01'))
            ->setCompanyName('Dast Invited Ltd');

        $response = (new PreEnquiryResponse())
            ->setIsCorrectCompanyName(false)
            ->setCompanyName(self::MARKERS['pre-enquiry'])
            ->setPreEnquiry($preEnquiry)
            ->setAnnualHgvJourneyEstimate(51)
            ->setInternationalJourneyHgvCount(20)
            ->setTotalHgvCount(70)
            ->setAnnualLgvJourneyEstimate(25)
            ->setInternationalJourneyLgvCount(10)
            ->setTotalLgvCount(30)
            ->setContactName('Dast Tester')
            ->setContactEmail('dast@example.com')
            ->setContactTelephone('8118181')
            ->setIsCorrectAddress(false)
            ->setContactAddress($this->address('Dast Seed Haulage Ltd', 'DA11 1AB'))
            ->setNumberOfEmployees(SurveyResponse::EMPLOYEES_10_TO_49);

        $this->entityManager->persist($user);
        $this->entityManager->persist($preEnquiry);
        $this->entityManager->persist($response);
        $this->entityManager->flush();

        return $user;
    }

    private function address(string $line1, string $postcode): LongAddress
    {
        return (new LongAddress())
            ->setLine1($line1)
            ->setLine2('1 Scan Road')
            ->setLine3('Dastbury')
            ->setLine4('Testshire')
            ->setLine5('')
            ->setLine6('')
            ->setPostcode($postcode);
    }

    /**
     * Just the path: the routes are host-bound, so the generator returns a network path when the console's request
     * context has a different host.
     */
    private function path(string $route, array $parameters): string
    {
        $url = $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
        return parse_url($url, PHP_URL_PATH);
    }
}
