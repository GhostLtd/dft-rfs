<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;
use Symfony\Component\Panther\ServerExtension;

class ActionUnloadingChangeTest extends AbstractActionTest
{
    public function testChangePlace(): void
    {
        $this->performChangeTest(false, 0, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/place#', 'place_continue', [
                new FormTestCase([
                    'place[place][name]' => 'Calais',
                    'place[place][country][country]' => 'FR',
                ]),
            ], $options);

            $expectedData['name'] = 'Calais';
            $expectedData['country'] = 'FR';
        });
    }

    public function testChangePlaceOtherCountry(): void
    {
        $this->performChangeTest(false, 0, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/place#', 'place_continue', [
                new FormTestCase([
                    'place[place][name]' => 'Kingston',
                    'place[place][country][country]' => 'other',
                    'place[place][country][country_other]' => 'Jamaica',
                ]),
            ], $options);

            $expectedData['name'] = 'Kingston';
            $expectedData['country'] = '0';
            $expectedData['countryOther'] = 'Jamaica';
        });
    }

    public function testChangeGoods(): void
    {
        $this->performChangeTest(false, 1, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/consignment-unloaded#', 'loading_place_continue', [
                new FormTestCase([
                    'loading_place[loadingAction]' => 'action-2',
                ]),
            ], $options);

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/weight-unloaded#', 'goods_unloaded_weight_continue', [
                new FormTestCase([
                    'goods_unloaded_weight[weightUnloadedAll]' => 'yes'
                ]),
            ], $options);

            $expectedData['loadingAction'] = $this->getInitialSecondLoadingData();
        });
    }

    public function testChangeGoodsPartUnload(): void
    {
        $this->performChangeTest(false, 1, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/consignment-unloaded#', 'loading_place_continue', [
                new FormTestCase([
                    'loading_place[loadingAction]' => 'action-2',
                ]),
            ], $options);

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/weight-unloaded#', 'goods_unloaded_weight_continue', [
                new FormTestCase([
                    'goods_unloaded_weight[weightUnloadedAll]' => 'no',
                    'goods_unloaded_weight[weightOfGoods]' => 5000,
                ]),
            ], $options);

            $expectedData['loadingAction'] = $this->getInitialSecondLoadingData();
            $expectedData['weightUnloadedAll'] = false;
            $expectedData['weightOfGoods'] = 5000;
        });
    }
}
