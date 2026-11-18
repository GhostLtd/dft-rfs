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
use App\DTO\RoRo\OperatorRoute;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\OperatorGroup;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Entity\RoRoUser;
use App\Entity\Route\Route;
use App\Form\RoRo\IntroductionType;
use App\Utility\PasscodeGenerator;
use App\Utility\RoRo\SurveyCreationHelper;
use App\Workflow\RoRo\RoRoState;
use App\Workflow\DomesticSurvey\DaySummaryState;
use App\Workflow\InternationalSurvey\TripState;
use App\Workflow\PreEnquiry\PreEnquiryState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

/**
 * Seeds an empty database with one passcode user per survey type, each with a part-completed survey, for the DAST
 * stack (bin/dast, docs/Dast.md). The data follows tests/DataFixtures (see the plan for which fixtures), but with
 * fixed marker strings that the ZAP plans look for to prove a page rendered the seeded data. Also a RoRo user (two
 * operators in one operator group, an active in-progress survey per route) and a pool of single-use login links for it.
 */
class DastSeeder
{
    // 8 digits, as passcode login requires
    public const array USERNAMES = [
        'domestic' => '90000001',
        'international' => '90000002',
        'pre-enquiry' => '90000003',
        'roro' => 'dast-roro@example.com',
    ];

    // The forms sections' sacrificial users (bin/dast's *-forms sections)
    public const array FORMS_USERNAMES = [
        'domestic' => '90000011',
        'international' => '90000012',
        'pre-enquiry' => '90000013',
    ];

    // The default size of the RoRo login-link pool (bin/dast's DAST_RORO_LINK_POOL overrides it, within 1-100)
    public const int RORO_LOGIN_LINKS = 20;
    private const int RORO_LOGIN_LINK_LIFETIME = 604800;

    // The RoRo operator group, and its operators (grouped by name prefix: OperatorSwitchHelper) with their routes
    private const string RORO_GROUP = 'Dast Ferries';
    private const array RORO_OPERATORS = [
        'Dast Ferries Dover' => [9901, [['Dover', 'Calais', 'roro-calais'], ['Dover', 'Dunkirk', 'roro-dunkirk']]],
        'Dast Ferries Harwich' => [9902, [['Harwich', 'Cuxhaven', 'roro-cuxhaven']]],
    ];

    // Plain letters, digits and spaces, so the ZAP plans can use them as literal regexes. Copied into bin/dast.
    public const array MARKERS = [
        'domestic-stops' => 'Dastford',
        'domestic-summary' => 'Zapton',
        'international-trip' => 'Scanville',
        'pre-enquiry' => 'Dast Seed Haulage Ltd',
        'admin-survey' => 'DA57 ZAP',
        // Each RoRo survey's comments
        'roro-calais' => 'Dast survey Calais',
        'roro-dunkirk' => 'Dast survey Dunkirk',
        'roro-cuxhaven' => 'Dast survey Cuxhaven',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PasscodeGenerator $passcodeGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoginLinkHandlerInterface $roroLoginLinkHandler,
        private readonly SurveyCreationHelper $roroSurveyCreationHelper,
    ) {}

    /**
     * @return array<string, string> KEY => value, in the order bin/dast documents
     */
    public function seed(int $roroLinks = self::RORO_LOGIN_LINKS): array
    {
        if ($roroLinks < 1 || $roroLinks > 100) {
            throw new \InvalidArgumentException("The RoRo login-link pool must hold 1-100 links, not {$roroLinks}");
        }
        foreach ([PasscodeUser::class, RoRoUser::class] as $userClass) {
            if ($this->entityManager->getRepository($userClass)->count([]) > 0) {
                throw new \RuntimeException('The DAST seeder only runs against a database with no users');
            }
        }

        [$domesticUser, $domesticSurvey] = $this->seedDomestic();
        [$internationalUser, $trip] = $this->seedInternational();
        $preEnquiryUser = $this->seedPreEnquiry();
        // The forms sections' sacrificial users: their forms are submitted (and attacked), so their data is expected to
        // change; nothing checks it
        [$domesticFormsUser] = $this->seedDomestic(self::FORMS_USERNAMES['domestic'], 'DA57 FRM');
        [$internationalFormsUser] = $this->seedInternational(self::FORMS_USERNAMES['international'], 'Formsville', 'FR01 FRM', 'dast-international-forms');
        $preEnquiryFormsUser = $this->seedPreEnquiry(self::FORMS_USERNAMES['pre-enquiry'], 'Dast Forms Haulage Ltd', 'dast-pre-enquiry-forms');
        [$roroUser, $harwich, $roroSurveys] = $this->seedRoRo();

        return [
            'DAST_DOMESTIC_USERNAME' => $domesticUser->getUsername(),
            'DAST_DOMESTIC_PASSWORD' => $this->passcodeGenerator->getPasswordForUser($domesticUser),
            'DAST_INTERNATIONAL_USERNAME' => $internationalUser->getUsername(),
            'DAST_INTERNATIONAL_PASSWORD' => $this->passcodeGenerator->getPasswordForUser($internationalUser),
            'DAST_PRE_ENQUIRY_USERNAME' => $preEnquiryUser->getUsername(),
            'DAST_PRE_ENQUIRY_PASSWORD' => $this->passcodeGenerator->getPasswordForUser($preEnquiryUser),
            'DAST_FORMS_DOMESTIC_USERNAME' => $domesticFormsUser->getUsername(),
            'DAST_FORMS_DOMESTIC_PASSWORD' => $this->passcodeGenerator->getPasswordForUser($domesticFormsUser),
            'DAST_FORMS_INTERNATIONAL_USERNAME' => $internationalFormsUser->getUsername(),
            'DAST_FORMS_INTERNATIONAL_PASSWORD' => $this->passcodeGenerator->getPasswordForUser($internationalFormsUser),
            'DAST_FORMS_PRE_ENQUIRY_USERNAME' => $preEnquiryFormsUser->getUsername(),
            'DAST_FORMS_PRE_ENQUIRY_PASSWORD' => $this->passcodeGenerator->getPasswordForUser($preEnquiryFormsUser),
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
            'DAST_RORO_LOGIN_LINKS' => implode(' ', self::issueRoRoLoginLinks($this->roroLoginLinkHandler, $roroUser, $roroLinks)),
            'DAST_PATH_RORO_HARWICH_DASHBOARD' => $this->path('app_roro_dashboard', ['operatorId' => $harwich->getId()]),
            'DAST_PATH_RORO_VEHICLE_COUNTS' => $this->roroEditPath($roroSurveys['roro-calais'], RoRoState::STATE_VEHICLE_COUNTS),
            'DAST_PATH_RORO_DATA_ENTRY' => $this->roroEditPath($roroSurveys['roro-calais'], RoRoState::STATE_DATA_ENTRY),
            'DAST_PATH_RORO_COMMENTS' => $this->roroEditPath($roroSurveys['roro-calais'], RoRoState::STATE_COMMENTS),
            'DAST_CHECK_RORO_CALAIS' => $this->roroEditPath($roroSurveys['roro-calais'], RoRoState::STATE_COMMENTS),
            'DAST_CHECK_RORO_DUNKIRK' => $this->roroEditPath($roroSurveys['roro-dunkirk'], RoRoState::STATE_COMMENTS),
            'DAST_CHECK_RORO_CUXHAVEN' => $this->roroEditPath($roroSurveys['roro-cuxhaven'], RoRoState::STATE_COMMENTS),
        ];
    }

    /**
     * Single-use login links for the RoRo user, as POST bodies for /roro/authenticate (expires=…&user=…&hash=…).
     *
     * The handler signs the user and expires = time() + lifetime, nothing random, so links made in the same second
     * would be one token. Each link's lifetime is one second longer than the last: a clock tick between calls can
     * only widen the gap between expiries, never cancel it.
     *
     * @return list<string>
     */
    public static function issueRoRoLoginLinks(LoginLinkHandlerInterface $handler, RoRoUser $user, int $count): array
    {
        $bodies = [];
        for ($i = 0; $i < $count; $i++) {
            $lifetime = self::RORO_LOGIN_LINK_LIFETIME - $count + 1 + $i;
            $bodies[] = (string) parse_url($handler->createLoginLink($user, null, $lifetime)->getUrl(), PHP_URL_QUERY);
        }
        return $bodies;
    }

    /** @return array{RoRoUser, Operator, array<string, RoRoSurvey>} the user, the Harwich operator, surveys by marker key */
    private function seedRoRo(): array
    {
        $this->entityManager->persist((new OperatorGroup())->setName(self::RORO_GROUP));

        $operators = [];
        foreach (self::RORO_OPERATORS as $name => [$code, $routes]) {
            $operator = (new Operator())->setName($name)->setCode($code)->setIsActive(true);
            foreach ($routes as [$ukPort, $foreignPort]) {
                $route = $this->entityManager->getRepository(Route::class)->getRouteByPortNames($ukPort, $foreignPort)
                    ?? throw new \RuntimeException("No {$ukPort}-{$foreignPort} route (the migrations seed it)");
                $operator->addRoute($route);
            }
            $this->entityManager->persist($operator);
            $operators[$name] = $operator;
        }

        $user = (new RoRoUser())
            ->setUsername(self::USERNAMES['roro'])
            ->setOperator($operators['Dast Ferries Dover']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Last month's surveys, as the monthly cron would create them, then made active and in progress with data:
        // a new survey shows none of the wizard pages beyond its introduction
        $periodStart = new \DateTime('first day of last month midnight');
        $surveys = [];
        foreach (self::RORO_OPERATORS as $name => [, $routes]) {
            foreach ($routes as [$ukPort, $foreignPort, $marker]) {
                $route = $this->entityManager->getRepository(Route::class)->getRouteByPortNames($ukPort, $foreignPort);
                $survey = $this->roroSurveyCreationHelper
                    ->createSurvey($periodStart, new OperatorRoute($operators[$name]->getId(), $route->getId()))
                    ->setState(SurveyStateInterface::STATE_IN_PROGRESS)
                    ->setIsActiveForPeriod(true)
                    ->setDataEntryMethod(IntroductionType::DATA_ENTRY_MANUAL_CHOICE)
                    ->setComments(self::MARKERS[$marker]);
                $count = 0;
                foreach (array_slice($survey->getVehicleCounts()->toArray(), 0, 3) as $vehicleCount) {
                    $vehicleCount->setVehicleCount(10 * ++$count);
                }
                $this->entityManager->persist($survey);
                $surveys[$marker] = $survey;
            }
        }
        $this->entityManager->flush();

        return [$user, $operators['Dast Ferries Harwich'], $surveys];
    }

    private function roroEditPath(RoRoSurvey $survey, string $state): string
    {
        return $this->path('app_roro_survey_edit', ['surveyId' => $survey->getId(), 'state' => $state]);
    }

    /** @return array{PasscodeUser, DomesticSurvey} */
    private function seedDomestic(string $username = self::USERNAMES['domestic'], string $registrationMark = self::MARKERS['admin-survey']): array
    {
        $user = (new PasscodeUser())->setUsername($username);

        $survey = (new DomesticSurvey())
            ->setSurveyPeriodStart(new \DateTime('2021-05-01'))
            ->setSurveyPeriodEnd(new \DateTime('2021-05-10'))
            ->setIsNorthernIreland(true)
            ->setRegistrationMark($registrationMark)
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

    /**
     * A second international user and a RoRo user outside the Dast Ferries group, each with data of their own, for
     * the cross-user access tests (tests/NewFunctional/AccessControl): they must never reach seed()'s users' data.
     * Call after seed().
     *
     * @return array{PasscodeUser, RoRoUser}
     */
    public function seedAccessControlUsers(): array
    {
        [$internationalUser] = $this->seedInternational('90000004', 'Probeton', 'BB02 BBB', 'dast-international-other');

        $operator = (new Operator())->setName('Outsider Ferries')->setCode(9903)->setIsActive(true);
        $route = $this->entityManager->getRepository(Route::class)->getRouteByPortNames('Harwich', 'Cuxhaven')
            ?? throw new \RuntimeException('No Harwich-Cuxhaven route (the migrations seed it)');
        $operator->addRoute($route);
        $roroUser = (new RoRoUser())->setUsername('dast-roro-outsider@example.com')->setOperator($operator);
        $this->entityManager->persist($operator);
        $this->entityManager->persist($roroUser);
        $this->entityManager->flush();

        $survey = $this->roroSurveyCreationHelper
            ->createSurvey(new \DateTime('first day of last month midnight'), new OperatorRoute($operator->getId(), $route->getId()))
            ->setState(SurveyStateInterface::STATE_IN_PROGRESS)
            ->setIsActiveForPeriod(true)
            ->setDataEntryMethod(IntroductionType::DATA_ENTRY_MANUAL_CHOICE)
            ->setComments('Outsider survey');
        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        return [$internationalUser, $roroUser];
    }

    /** @return array{PasscodeUser, Trip} */
    private function seedInternational(
        string $username = self::USERNAMES['international'],
        string $place = self::MARKERS['international-trip'],
        string $registrationMark = 'AA01 ABC',
        string $reference = 'dast-international',
    ): array
    {
        $user = (new PasscodeUser())->setUsername($username);
        $company = (new Company())->setBusinessName('Dast International Ltd');

        $survey = (new InternationalSurvey())
            ->setSurveyPeriodStart(new \DateTime('2021-05-01'))
            ->setSurveyPeriodEnd(new \DateTime('2021-05-10'))
            ->setPasscodeUser($user)
            ->setState(SurveyStateInterface::STATE_IN_PROGRESS)
            ->setCompany($company)
            ->setInvitationEmails("{$reference}@example.com")
            ->setInvitationAddress(new LongAddress())
            ->setReferenceNumber($reference);

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
            ->setRegistrationMark($registrationMark)
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
            ->setOrigin($place)
            ->setDestination($place);
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

    private function seedPreEnquiry(string $username = self::USERNAMES['pre-enquiry'], string $companyName = self::MARKERS['pre-enquiry'], string $reference = 'dast-pre-enquiry'): PasscodeUser
    {
        $user = (new PasscodeUser())->setUsername($username);

        $preEnquiry = (new PreEnquiry())
            ->setPasscodeUser($user)
            ->setState(SurveyStateInterface::STATE_IN_PROGRESS)
            ->setReferenceNumber($reference)
            ->setInvitationAddress($this->address('Dast Invited Ltd', 'DA10 1AB'))
            ->setInvitationEmails("{$reference}@example.com")
            ->setDispatchDate(new \DateTime('2021-06-01'))
            ->setCompanyName('Dast Invited Ltd');

        $response = (new PreEnquiryResponse())
            ->setIsCorrectCompanyName(false)
            ->setCompanyName($companyName)
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
