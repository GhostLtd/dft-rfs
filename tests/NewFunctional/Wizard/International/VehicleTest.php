<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Entity\International\Vehicle;
use App\Entity\Vehicle as VehicleLookup;
use App\Repository\International\VehicleRepository;
use App\Tests\DataFixtures\International\ResponseStillActiveFixtures;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTestCase;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;
use Facebook\WebDriver\WebDriverBy;

class VehicleTest extends AbstractVehicleTestCase
{
    public function testVehicleDidNotLeaveUk(): void
    {
        $this->initialiseTest([ResponseStillActiveFixtures::class]);

        $this->clickLinkContaining('Add vehicle');

        $this->vehicleDetails(false);
        $this->pathTestAction('/international-survey');

        // confirm-dates 'no' transition surfaces a banner that pulls the registration mark
        // from the wizard subject via the workflow's translation_parameters config.
        $this->callbackTestAction(function (Context $context) {
            $banner = $context->getClient()->findElement(
                WebDriverBy::cssSelector('.govuk-notification-banner__content')
            );
            $context->getTestCase()->assertStringContainsString(
                'AB01ABC',
                $banner->getText(),
                'Expected notification banner to contain the rejected vehicle registration mark'
            );
        });

        $this->assertNoVehiclesInDatabase();
    }

    public function testVehicleThatDidLeave(): void
    {
        $this->initialiseTest([ResponseStillActiveFixtures::class]);

        $this->clickLinkContaining('Add vehicle');

        $this->vehicleDetails(true);
        $this->vehicleConfiguration();

        $this->pathTestAction('#^/international-survey/vehicles/[a-f0-9\-]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $expectedData = $this->getExpectedData();
        $this->assertDashboardMatchesData($expectedData);
        $this->assertDatabaseMatchesData($expectedData);
    }

    public function vehicleConfiguration(): void
    {
        $this->formTestAction('/international-survey/add-vehicle/trailer-configuration', 'vehicle_trailer_configuration_button_group_continue', [
            new FormTestCase([
                'vehicle_trailer_configuration[trailerConfiguration]' => "300",
            ]),
        ]);

        $this->formTestAction('/international-survey/add-vehicle/axle-configuration', 'vehicle_axle_configuration_button_group_continue', [
            new FormTestCase([
                'vehicle_axle_configuration[axleConfiguration]' => "321",
            ]),
        ]);

        $this->formTestAction('/international-survey/add-vehicle/vehicle-body', 'vehicle_body_button_group_continue', [
            new FormTestCase([
                'vehicle_body[bodyType]' => 'liquid',
            ]),
        ]);

        $this->formTestAction('/international-survey/add-vehicle/vehicle-weight', 'vehicle_weight_button_group_continue', [
            new FormTestCase([
                'vehicle_weight[carryingCapacity]' => '35000',
                'vehicle_weight[grossWeight]' => '40000',
            ]),
        ]);
    }

    protected function vehicleDetails(bool $vehicleDepartedDuringSurveyPeriod): void
    {
        $this->formTestAction('/international-survey/add-vehicle/vehicle-registration', 'vehicle_details_button_group_continue', [
            new FormTestCase([
                'vehicle_details[registrationMark]' => 'AB01 ABC',
                'vehicle_details[operationType]' => 'for-hire-and-reward',
            ]),
        ]);

        $this->formTestAction('/international-survey/add-vehicle/confirm-dates', 'confirm_dates_button_group_continue', [
            new FormTestCase([
                'confirm_dates[confirm]' => ($vehicleDepartedDuringSurveyPeriod ? 'yes' : 'no'),
            ]),
        ]);
    }

    protected function assertNoVehiclesInDatabase(): void
    {
        $this->callbackTestAction(function (Context $context) {
            $vehicles = $context->getEntityManager()->getRepository(Vehicle::class)->findAll();

            $context->getTestCase()->assertCount(0, $vehicles, "Expected number of vehicles in the database to be zero");
        });
    }

    public function getExpectedData(): array
    {
        return [
            'registrationMark' => 'AB01ABC',
            'operationType' => 'for-hire-and-reward',
            'grossWeight' => 40000,
            'carryingCapacity' => '35000',
            'trailerConfiguration' => 300,
            'axleConfiguration' => 321,
            'bodyType' => 'liquid',
        ];
    }
}
