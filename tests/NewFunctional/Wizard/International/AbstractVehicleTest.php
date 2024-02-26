<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Tests\DataFixtures\International\ResponseStillActiveFixtures;
use App\Tests\DataFixtures\International\VehicleFixtures;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Utility\RegistrationMarkHelper;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractVehicleTest extends AbstractSurveyTest
{
    protected function performChangeTest(int $linkIndex, \Closure $callback): void
    {
        $this->initialiseTest([VehicleFixtures::class]);
        $this->pathTestAction('/international-survey');
        $this->clickLinkContaining('View', 1);
        $this->pathTestAction('#^\/international-survey\/vehicles\/[a-f0-9-]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $data = $this->getInitialData();

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);

        $this->clickLinkContaining('Change', $linkIndex);

        $callback($data);

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    protected function getInitialData(): array
    {
        return [
            'registrationMark' => 'AA01ABC',
            'axleConfiguration' => 333,
            'trailerConfiguration' => 300,
            'bodyType' => 'curtain-sided',
            'carryingCapacity' => 35000,
            'grossWeight' => 28000,
            'operationType' => 'on-own-account',
        ];
    }

    protected function assertDashboardMatchesData(array $data): void
    {
        $registrationMarkHelper = new RegistrationMarkHelper($data['registrationMark']);

        // N.B. Match statements non-exhaustive; only catering for the
        //      options used in the tests...
        $expectedData = [
            'Registration' => $registrationMarkHelper->getFormattedRegistrationMark(),
            'Operation type' => match($data['operationType']) {
                'on-own-account' => 'On own account',
                'for-hire-and-reward' => 'For hire and reward',
            },
            'Gross' => number_format($data['grossWeight']).' kg',
            'Carrying' => number_format($data['carryingCapacity']).' kg',
            'Vehicle/trailer body type' => match($data['bodyType']) {
                'curtain-sided' => 'Curtain-sided',
                'liquid' => 'Liquid tanker',
                'tipper' => 'Tipper',
            },
            'Axle config' => match($data['axleConfiguration']) {
                140 => 'Rigid 4 axle',
                321 => 'Articulated 2 axle + 1 axle trailer',
                333 => 'Articulated 3 axle + 3 axle trailer',
            },
        ];

        $this->assertSummaryListData($expectedData);
    }

    protected function assertDatabaseMatchesData(array $data): void
    {
        $this->callbackTestAction(function (Context $context) use ($data) {
            $test = $context->getTestCase();
            $survey = $this->getSurvey($context->getEntityManager(), $test);

            $response = $survey->getResponse();
            $vehicles = $response->getVehicles();

            $this->assertCount(1, $vehicles, 'Expect only one vehicle in the database');

            $this->assertDataMatches($vehicles[0], $data, 'vehicle');
        });
    }
}
