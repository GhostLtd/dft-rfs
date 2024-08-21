<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class ActionLoadingChangeTest extends AbstractActionTest
{
    public function testChangePlace(): void
    {
        $this->performChangeTest(true, 0, function(array &$expectedData) {
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
        $this->performChangeTest(true, 0, function(array &$expectedData) {
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
        $this->performChangeTest(true, 1, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/goods-description#', 'goods_description_continue', [
                new FormTestCase([
                    'goods_description[goodsDescription]' => 'packaging',
                ]),
            ], $options);

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/weight-loaded#', 'goods_loaded_weight_continue', [
                new FormTestCase([
                    'goods_loaded_weight[weightOfGoods]' => '19019',
                ]),
            ], $options);

            $expectedData['goodsDescription'] = 'packaging';
            $expectedData['goodsDescriptionOther'] = null;
            $expectedData['weightOfGoods'] = 19019;
        });
    }

    public function testChangeGoodsOther(): void
    {
        $this->performChangeTest(true, 1, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/goods-description#', 'goods_description_continue', [
                new FormTestCase([
                    'goods_description[goodsDescription]' => 'other-goods',
                    'goods_description[goodsDescriptionOther]' => 'Bananas',
                ]),
            ], $options);

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/weight-loaded#', 'goods_loaded_weight_continue', [
                new FormTestCase([
                    'goods_loaded_weight[weightOfGoods]' => '20202',
                ]),
            ], $options);

            $expectedData['goodsDescription'] = 'other-goods';
            $expectedData['goodsDescriptionOther'] = 'Bananas';
            $expectedData['weightOfGoods'] = 20202;
        });
    }

    public function testChangeNonHazardous(): void
    {
        $this->performChangeTest(true, 3, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/hazardous-goods#', 'hazardous_goods_continue', [
                new FormTestCase([
                    'hazardous_goods[isHazardousGoods]' => 'no',
                ]),
            ], $options);

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/cargo-type#', 'cargo_type_continue', [
                new FormTestCase([
                    'cargo_type[cargoTypeCode]' => 'LFC',
                ]),
            ], $options);

            $expectedData['hazardousGoodsCode'] = '0';
            $expectedData['cargoTypeCode'] = 'LFC';
        });
    }

    public function testChangeHazardous(): void
    {
        $this->performChangeTest(true, 3, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/hazardous-goods#', 'hazardous_goods_continue', [
                new FormTestCase([
                    'hazardous_goods[isHazardousGoods]' => 'yes',
                    'hazardous_goods[hazardousGoodsCode]' => '4.2',
                ]),
            ], $options);

            $this->formTestAction('#^\/international-survey\/actions\/[a-f0-9-]+\/edit/cargo-type#', 'cargo_type_continue', [
                new FormTestCase([
                    'cargo_type[cargoTypeCode]' => 'OT',
                ]),
            ], $options);

            $expectedData['hazardousGoodsCode'] = '4.2';
            $expectedData['cargoTypeCode'] = 'OT';
        });
    }
}
