<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Entity\International\Action;
use App\Tests\DataFixtures\International\ActionExtendedFixtures;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Countries;

abstract class AbstractActionTest extends AbstractSurveyTest
{
    protected function performChangeTest(bool $isLoading, int $linkIndex, \Closure $callback): void
    {
        $this->initialiseTest([ActionExtendedFixtures::class]);
        $this->pathTestAction('/international-survey');
        $this->clickLinkContaining('View', 1); // N.B. 0th link is "Correspondence / business details"
        $this->pathTestAction('#^\/international-survey\/vehicles\/[a-f0-9-]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
        $this->clickLinkContaining('View'); // View trip
        $this->pathTestAction('#^\/international-survey\/trips\/[a-f0-9-]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        if ($isLoading) {
            $this->clickLinkContaining('View', 0);
            $data = $this->getInitialLoadingData();
        } else {
            $this->clickLinkContaining('View', 2);
            $data = $this->getInitialUnloadingData();
        }

        $this->pathTestAction('#^\/international-survey\/actions\/[a-f0-9-]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->assertDashboardMatchesData($data, $isLoading);
        $this->assertDatabaseMatchesData($data, $isLoading);

        $this->clickLinkContaining('Change', $linkIndex);

        $callback($data);

        $this->assertDashboardMatchesData($data, $isLoading);
        $this->assertDatabaseMatchesData($data, $isLoading);
    }

    protected function getInitialLoadingData(): array
    {
        return [
            'number' => 1,
            'name' => 'Chichester',
            'country' => 'GB',
            'countryOther' => null,
            'loadingAction' => null,
            'goodsDescription' => 'other-goods',
            'goodsDescriptionOther' => 'Waste Chemicals',
            'weightOfGoods' => 23000,
            'hazardousGoodsCode' => '8',
            'cargoTypeCode' => 'LB',
        ];
    }

    protected function getInitialUnloadingData(): array
    {
        return [
            'number' => 3,
            'name' => 'Duisburg',
            'country' => 'DE',
            'countryOther' => null,
            'loadingAction' => $this->getInitialLoadingData(),
            'weightUnloadedAll' => true,
            'weightOfGoods' => null,

            'cargoTypeCode' => null,
            'goodsDescription' => null,
            'goodsDescriptionOther' => null,
            'hazardousGoodsCode' => null,
        ];
    }

    protected function getInitialSecondLoadingData(): array
    {
        return [
            'number' => 2,
            'name' => 'Bognor Regis',
            'country' => 'GB',
            'countryOther' => null,
            'loadingAction' => null,
            'goodsDescription' => 'other-goods',
            'goodsDescriptionOther' => 'Bread',
            'weightOfGoods' => 12000,
            'hazardousGoodsCode' => null,
            'cargoTypeCode' => 'RC',
        ];
    }

    protected function assertDashboardMatchesData(array $data, bool $isLoading): void
    {
        $getCountryName = fn(array $data) =>
            ($data['country'] && $data['country'] !== '0') ?
                Countries::getName($data['country']) :
                $data['countryOther'];

        $getGoodsDescription = fn(array $data) =>
            $data['goodsDescription'] === 'other-goods' ?
                $data['goodsDescriptionOther'] :
                match($data['goodsDescription']) {
                    'packaging' => 'Packaging',
                };

        $expectedData = [
            'Place' => "{$data['name']}, {$getCountryName($data)}",
        ];

        $weightOfGoods = null;

        if ($isLoading) {
            $expectedData['Hazardous goods?'] = match ($data['hazardousGoodsCode']) {
                '0' => 'Not hazardous',
                '4.2' => '4.2 - Spontaneously combustible substance',
                '8' => '8 - Corrosive substance',
            };

            $expectedData['Cargo type'] = match ($data['cargoTypeCode']) {
                'LB' => 'Liquid bulk',
                'LFC' => 'Large freight containers',
                'OT' => 'Other cargo types',
            };

            $expectedData['Goods'] = $getGoodsDescription($data);
        } else {
            $loadingAction = $data['loadingAction'];
            $expectedData['Goods'] = "{$getGoodsDescription($loadingAction)} from {$loadingAction['number']}. {$loadingAction['name']}, {$getCountryName($loadingAction)}";

            if ($data['weightUnloadedAll'] ?? null) {
                $weightOfGoods = 'All '.number_format($loadingAction['weightOfGoods']).' kg';
            }
        }

        if (!$weightOfGoods) {
            $weightOfGoods = number_format($data['weightOfGoods']) . ' kg';
        }

        $expectedData['Weight'] = $weightOfGoods;

        $this->assertSummaryListData($expectedData);
    }

    protected function assertDatabaseMatchesData(array $data, bool $isLoading): void
    {
        $this->callbackTestAction(function (Context $context) use ($data, $isLoading) {
            $action = $this->getAction($this->context->getEntityManager(), $context->getTestCase(), $isLoading);
            $this->assertDataMatches($action, $data, 'action');
        });
    }

    protected function getAction(EntityManagerInterface $entityManager, TestCase $test, bool $isLoading): Action
    {
        $trip = $this->getTrip($entityManager, $test);

        $actions = array_values(array_filter(
            $trip->getActions()->toArray(),
            fn(Action $action) => $action->getLoading() === $isLoading
        ));

        $expectedCount = $isLoading ? 2 : 1;
        $adjective = $isLoading ? 'loading' : 'unloading';
        $test->assertCount($expectedCount, $actions, "Unexpected number of {$adjective}Actions (Expected {$expectedCount})");

        return $actions[0];
    }
}
