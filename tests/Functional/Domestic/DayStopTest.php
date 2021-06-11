<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\BusinessFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndTestCase;
use App\Tests\Functional\Wizard\WizardStepTestCase;

class DayStopTest extends AbstractWizardTest
{
    protected function dayStopData(bool $goodsLoaded, ?bool $goodsUnloaded=null, ?bool $isEmpty=null, ?bool $isHazardous=null, ?bool $atCapacityBySpace=null, ?bool $atCapacityByWeight=null): array
    {
        $originTests = $this->getLocationFormTestCases('origin', $goodsLoaded, 'Portsmouth');
        $testData = [
            new WizardStepTestCase("How many times did you stop on day 1?", "number_of_stops_continue", [
                new FormTestCase([], [
                    "#number_of_stops_hasMoreThanFiveStops",
                ]),
                new FormTestCase([
                    "number_of_stops" => [
                        "hasMoreThanFiveStops" => "0",
                    ],
                ])
            ]),
            new WizardStepTestCase("Where did the vehicle begin this stage of the journey?", "origin_continue", $originTests),
        ];

        $destinationTitle = "Where did this stage of the journey end?";
        $borderCrossingTitle = "Border crossing";
        $hazardousGoodsTitle = "Dangerous or hazardous goods";
        $goodsWeightTitle = "Goods weight and vehicle capacity";

        if ($goodsUnloaded === null) {
            return array_merge($testData, [new WizardStepTestCase($destinationTitle)]);
        }

        $destinationTests = $this->getLocationFormTestCases('destination', $goodsUnloaded, 'Worthing');
        $testData = array_merge($testData, [
            new WizardStepTestCase($destinationTitle, "destination_continue", $destinationTests),
        ]);

        if ($isEmpty === null) {
            return array_merge($testData, [new WizardStepTestCase($borderCrossingTitle)]);
        }

        $goodsDescriptionTests = $this->getGoodsDescriptionTests($isEmpty);
        $testData = array_merge($testData, [
            new WizardStepTestCase($borderCrossingTitle, "border_crossing_continue", [
                new FormTestCase([
                    "border_crossing" => [
                        "borderCrossingLocation" => "Hove", // N.B. This field can be left blank
                    ],
                ]),
            ]),
            new WizardStepTestCase("Distance travelled", "distance_travelled_continue", [
                new FormTestCase([], [
                    "#distance_travelled_distanceTravelled_value",
                    "#distance_travelled_distanceTravelled_unit",
                ]),
                new FormTestCase([
                    "distance_travelled" => [
                        "distanceTravelled" => [
                            "value" => 120,
                            "unit" => "miles"
                        ]
                    ]
                ])
            ]),
            new WizardStepTestCase("Description of goods carried", "goods_description_continue", $goodsDescriptionTests),
        ]);

        if (!$isEmpty) {
            if ($isHazardous === null) {
                return array_merge($testData, [new WizardStepTestCase($hazardousGoodsTitle)]);
            }

            $hazardousTests = $this->getHazardousTests($isHazardous);
            $testData = array_merge($testData, [
                new WizardStepTestCase($hazardousGoodsTitle, "hazardous_goods_continue", $hazardousTests),
                new WizardStepTestCase("How were the goods carried?", "cargo_type_continue", [
                    new FormTestCase([], [
                        "#cargo_type_cargoTypeCode",
                    ]),
                    new FormTestCase([
                        "cargo_type" => [
                            "cargoTypeCode" => "RC",
                        ],
                    ])
                ]),

            ]);

            if ($atCapacityBySpace === null || $atCapacityByWeight === null) {
                return array_merge($testData, [new WizardStepTestCase($goodsWeightTitle)]);
            }

            $weightTests = [
                new FormTestCase([], [
                    "#goods_weight_weightOfGoodsCarried",
                    "#goods_weight_wasAtCapacity",
                ]),
            ];

            if (!$atCapacityByWeight  && !$atCapacityBySpace) {
                $weightTests = array_merge($weightTests, [
                    new FormTestCase([
                        "goods_weight" => [
                            "weightOfGoodsCarried" => "100",
                            "wasAtCapacity" => "0"
                        ],
                    ])
                ]);

            } else {
                if ($atCapacityBySpace && !$atCapacityByWeight) {
                    $wasLimitedBy = [0 => "space"];
                } else if (!$atCapacityBySpace && $atCapacityByWeight) {
                    $wasLimitedBy = [1 => "weight"];
                } else {
                    $wasLimitedBy = [0 => "space", 1 => "weight"];
                }

                $weightTests = array_merge($weightTests, [
                    new FormTestCase([
                        "goods_weight" => [
                            "weightOfGoodsCarried" => "100",
                            "wasAtCapacity" => "1"
                        ],
                    ], [
                        "#goods_weight_wasLimitedBy",
                    ]),
                    new FormTestCase([
                        "goods_weight" => [
                            "weightOfGoodsCarried" => "100",
                            "wasAtCapacity" => "1",
                            "wasLimitedBy" => $wasLimitedBy,
                        ],
                    ]),
                ]);
            }

            $testData = array_merge($testData, [
                new WizardStepTestCase($goodsWeightTitle, "goods_weight_continue", $weightTests),
            ]);
        }

        return array_merge($testData, [
            new WizardEndTestCase('Day record', WizardEndTestCase::MODE_CONTAINS)
        ]);
    }

    protected function getLocationFormTestCases(string $prefix, bool $loaded, string $location): array
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

    public function wizardData(): array
    {
        return [
            'Goods loaded' => [$this->dayStopData(true)],
            'No goods loaded' => [$this->dayStopData(false)],
            'Goods unloaded' => [$this->dayStopData(true, true)],
            'No goods unloaded' => [$this->dayStopData(true, false)],
            'Empty' => [$this->dayStopData(true, true, true)],
            'Bread' => [$this->dayStopData(true, true, false)],
            'Hazardous' => [$this->dayStopData(true, true, false, true)],
            'Non-hazardous' => [$this->dayStopData(true, true, false, false)],
            'Not at capacity' => [$this->dayStopData(true, true, false, false, false, false)],
            'At capacity by space' => [$this->dayStopData(true, true, false, false, true, false)],
            'At capacity by weight' => [$this->dayStopData(true, true, false, false, false, true)],
            'At capacity by space and weight' => [$this->dayStopData(true, true, false, false, true, true)],
        ];
    }

    protected function getGoodsDescriptionTests(bool $isEmpty): array
    {
        $goodsDescriptionTests = [
            new FormTestCase([], ["#goods_description_goodsDescription"]),
        ];

        if ($isEmpty) {
            $goodsDescriptionTests[] = new FormTestCase([
                "goods_description" => [
                    "goodsDescription" => "empty",
                ],
            ]);
        } else {
            $goodsDescriptionTests[] = new FormTestCase([
                "goods_description" => [
                    "goodsDescription" => "other-goods",
                ],
            ], [
                "#goods_description_goodsDescriptionOther"
            ]);

            $goodsDescriptionTests[] = new FormTestCase([
                "goods_description" => [
                    "goodsDescription" => "other-goods",
                    "goodsDescriptionOther" => "Bread",
                ]
            ]);
        }

        return $goodsDescriptionTests;
    }

    protected function getHazardousTests(bool $isHazardous): array
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

    /**
     * @dataProvider wizardData
     */
    public function testDayStopWizard($wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([BusinessFixtures::class]);
        $browser->request('GET', '/domestic-survey/day-1');

        $this->doWizardTest($browser, $wizardData);
    }
}