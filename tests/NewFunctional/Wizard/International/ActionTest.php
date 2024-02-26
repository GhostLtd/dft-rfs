<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Tests\DataFixtures\International\LoadingActionFixtures;
use App\Tests\DataFixtures\International\TripFixtures;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTest;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;
use Symfony\Component\Panther\ServerExtension;

class ActionTest extends AbstractPasscodeWizardTest
{
    public function testLoading(): void
    {
        $this->initialiseTest([TripFixtures::class]);

        $this->clickLinkContaining('View', 1); // N.B. 0th link is "Correspondence / business details"
        $this->clickLinkContaining('View'); // View trip
        $this->clickLinkContaining('Add loading action');

        $this->formTestAction('#^/international-survey/trips/[^/]+/add-action/place#', 'place_continue', [
            new FormTestCase([
                'place[loading]' => 'load',
                'place[place][name]' => 'Whoopie',
                'place[place][country][country]' => 'GB',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/trips/[^/]+/add-action/goods-description#', 'goods_description_continue', [
            new FormTestCase([
                'goods_description[goodsDescription]' => 'other-goods',
                'goods_description[goodsDescriptionOther]' => 'Fruits and vegetables',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);


        $this->formTestAction('#^/international-survey/trips/[^/]+/add-action/weight-loaded#', 'goods_loaded_weight_continue', [
            new FormTestCase([
                'goods_loaded_weight[weightOfGoods]' => '1000',

            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/trips/[^/]+/add-action/hazardous-goods#', 'hazardous_goods_continue', [
            new FormTestCase([
                'hazardous_goods[isHazardousGoods]' => 'yes',
                'hazardous_goods[hazardousGoodsCode]' => '4.1',

            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/trips/[^/]+/add-action/cargo-type#', 'cargo_type_continue', [
            new FormTestCase([
                'cargo_type[cargoTypeCode]' => 'PL',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->pathTestAction('#^/international-survey/trips/[^/]+/add-action/add-another#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->callbackTestAction(function (Context $context) {
            $actions = $context->getEntityManager()->getRepository(Action::class)->findAll();

            $test = $context->getTestCase();
            $test->assertCount(1, $actions, "Expected number of actions in the database to be one");

            $action = $actions[0];

            $test->assertEquals(1, $action->getNumber());
            $test->assertEquals('Whoopie', $action->getName());
            $test->assertEquals('GB', $action->getCountry());
            $test->assertEquals('other-goods', $action->getGoodsDescription());
            $test->assertEquals('Fruits and vegetables', $action->getGoodsDescriptionOther());
            $test->assertEquals(1000, $action->getWeightOfGoods());
            $test->assertEquals('4.1', $action->getHazardousGoodsCode());
            $test->assertEquals('PL', $action->getCargoTypeCode());
            $test->assertEquals(true, $action->getLoading());
            $test->assertNull($action->getLoadingAction());
        });
    }

    public function testUnloading(): void
    {
        $this->initialiseTest([LoadingActionFixtures::class]);

        $this->clickLinkContaining('View', 1); // N.B. 0th link is "Correspondence / business details"
        $this->clickLinkContaining('View'); // View trip
        $this->clickLinkContaining('Add loading or unloading action');

        $this->formTestAction('#^/international-survey/trips/[^/]+/add-action/place#', 'place_continue', [
            new FormTestCase([
                'place[loading]' => 'unload',
                'place[place][name]' => 'Ogre',
                'place[place][country][country]' => 'other',
                'place[place][country][country_other]' => 'Latvia',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/trips/[^/]+/add-action/consignment-unloaded#', 'loading_place_continue', [
            new FormTestCase([
                'loading_place[loadingAction]' => 'action-1',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/trips/[^/]+/add-action/weight-unloaded#', 'goods_unloaded_weight_continue', [
            new FormTestCase([
                'goods_unloaded_weight[weightUnloadedAll]' => 'no',
                'goods_unloaded_weight[weightOfGoods]' => '10000',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->pathTestAction('#^/international-survey/trips/[^/]+/add-action/add-another#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->callbackTestAction(function (Context $context) {
            $actions = $context->getEntityManager()->getRepository(Action::class)->findBy([], ['number' => 'ASC']);

            $test = $context->getTestCase();
            $test->assertCount(2, $actions, "Expected number of actions in the database to be two");

            $action = $actions[1];

            $test->assertEquals(2, $action->getNumber());
            $test->assertEquals('Ogre', $action->getName());
            $test->assertEquals('0', $action->getCountry());
            $test->assertEquals('Latvia', $action->getCountryOther());
            $test->assertEquals(false, $action->getWeightUnloadedAll());
            $test->assertEquals(10000, $action->getWeightOfGoods());
            $test->assertEquals(false, $action->getLoading());
            $test->assertNotNull($action->getLoadingAction());
        });
    }

    public function testAddAnotherNo(): void
    {
        [$tripDashboardPath, $addAnotherPath] = $this->initialiseAddAnotherAndGetPaths();

        $this->formTestAction($addAnotherPath, 'add_another_continue', [
            new FormTestCase([
                'add_another[confirm]' => 'no',
            ]),
        ]);

        $this->pathTestAction($tripDashboardPath);
    }

    public function testAddAnotherYes(): void
    {
        [$_, $addAnotherPath] = $this->initialiseAddAnotherAndGetPaths();

        $this->formTestAction($addAnotherPath, 'add_another_continue', [
            new FormTestCase([
                'add_another[confirm]' => 'yes',
            ]),
        ]);

        $this->pathTestAction('#^/international-survey/trips/[^/]+/add-action/place#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    protected function initialiseAddAnotherAndGetPaths(): array
    {
        // Since this is a valid starting point, we can just test it separately
        $this->initialiseTest([LoadingActionFixtures::class]);

        $this->clickLinkContaining('View', 1); // N.B. 0th link is "Correspondence / business details"
        $this->clickLinkContaining('View'); // View trip

        $tripDashboardPath = $this->getCurrentPath();

        if (!preg_match('#^/international-survey/trips/(?P<tripId>[^/]+)$#', $tripDashboardPath, $matches)) {
            $this->fail('Failed to determine tripId');
        }

        $tripId = $matches['tripId'];
        $addAnotherPath = "/international-survey/trips/{$tripId}/add-action/add-another";

        $this->client->get($addAnotherPath);
        return [$tripDashboardPath, $addAnotherPath];
    }
}
