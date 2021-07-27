<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\Functional\Wizard\FormTestCase;

abstract class AbstractDayStopOrSummaryData
{
    protected static function getLocationFormTestCases(string $prefix, bool $loaded, string $location): array
    {
        if ($prefix === 'origin') {
            $transferred = 'Loaded';
            $direction = 'From';
        } else if ($prefix === 'destination') {
            $transferred = 'Unloaded';
            $direction = 'To';
        } else {
            throw new \RuntimeException('Invalid prefix');
        }

        $formTests = [
            new FormTestCase([], [
                "#{$prefix}_{$prefix}Location",
                "#{$prefix}_goods{$transferred}",
            ]),
        ];

        if ($loaded) {
            $formTests[] =
                new FormTestCase([
                    "{$prefix}" => [
                        "{$prefix}Location" => $location,
                        "goods{$transferred}" => "1",
                    ],
                ], [
                    "#{$prefix}_goodsTransferred{$direction}",
                ]);

            $formTests[] =
                new FormTestCase([
                    "{$prefix}" => [
                        "{$prefix}Location" => $location,
                        "goods{$transferred}" => "1",
                        "goodsTransferred{$direction}" => "0"
                    ],
                ]);
        } else {
            $formTests[] =
                new FormTestCase([
                    "{$prefix}" => [
                        "{$prefix}Location" => $location,
                        "goods{$transferred}" => "0",
                    ],
                ]);
        }

        return $formTests;
    }

    protected static function getHazardousTests(bool $isHazardous): array
    {
        $hazardousTests = [
            new FormTestCase([], [
                "#hazardous_goods_isHazardousGoods",
            ])
        ];

        if ($isHazardous) {
            $hazardousTests[] = new FormTestCase([
                "hazardous_goods" => [
                    "isHazardousGoods" => "1",
                ]
            ], [
                "#hazardous_goods_hazardousGoodsCode",
            ]);

            $hazardousTests[] = new FormTestCase([
                "hazardous_goods" => [
                    "isHazardousGoods" => "1",
                    "hazardousGoodsCode" => "7", // Radioactive bread
                ]
            ]);
        } else {
            $hazardousTests[] = new FormTestCase([
                "hazardous_goods" => [
                    "isHazardousGoods" => "0",
                ]
            ]);
        }

        return $hazardousTests;
    }
}