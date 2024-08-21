<?php

namespace App\Tests\Form\Type\Domestic\DaySummary;

use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DaySummary\DistanceTravelledType;
use App\Tests\Form\Type\AbstractTypeTest;

class DistanceTravelledTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [[
                'distanceTravelledLoaded' => ['value' => '', 'unit' => ''],
                'distanceTravelledUnloaded' => ['value' => '', 'unit' => ''],
            ], false],
            [[
                'distanceTravelledLoaded' => ['value' => '27', 'unit' => 'miles'],
                'distanceTravelledUnloaded' => ['value' => '', 'unit' => ''],
            ], false],
            [[
                'distanceTravelledLoaded' => ['value' => '', 'unit' => ''],
                'distanceTravelledUnloaded' => ['value' => '27', 'unit' => 'miles'],
            ], false],

            [
                [
                    'distanceTravelledLoaded' => ['value' => '27', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '34', 'unit' => 'miles'],
                ],
                true,
                '27.0',
                'miles',
                '34.0',
                'miles',
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '27', 'unit' => 'kilometres'],
                    'distanceTravelledUnloaded' => ['value' => '34', 'unit' => 'miles'],
                ],
                true,
                '27.0',
                'kilometres',
                '34.0',
                'miles',
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '27', 'unit' => 'kilometers'],
                    'distanceTravelledUnloaded' => ['value' => '34', 'unit' => 'bananas'],
                ],
                false,
            ],

            // Number extremities (loaded)
            [
                [
                    'distanceTravelledLoaded' => ['value' => '0', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '34', 'unit' => 'miles'],
                ],
                true,
                '0.0',
                'miles',
                '34.0',
                'miles',
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '27.5', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '34', 'unit' => 'miles'],
                ],
                true,
                '27.5',
                'miles',
                '34.0',
                'miles',
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '999999999.9', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '34', 'unit' => 'miles'],
                ],
                true,
                '999999999.9',
                'miles',
                '34.0',
                'miles',
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '9999999999.9', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '34', 'unit' => 'miles'],
                ],
                false, // Field has precision 10, scale 1
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '27.53', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '34', 'unit' => 'miles'],
                ],
                false, // Field has scale 1
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '-1.2', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '34', 'unit' => 'miles'],
                ],
                false,
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '2e5', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '34', 'unit' => 'miles'],
                ],
                false,
            ],

            // Number extremities (unloaded)
            [
                [
                    'distanceTravelledLoaded' => ['value' => '34', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '0', 'unit' => 'miles'],
                ],
                true,
                '34.0',
                'miles',
                '0.0',
                'miles',
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '34', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '27.5', 'unit' => 'miles'],
                ],
                true,
                '34.0',
                'miles',
                '27.5',
                'miles',
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '34', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '999999999.9', 'unit' => 'miles'],
                ],
                true,
                '34.0',
                'miles',
                '999999999.9',
                'miles',
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '34', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '9999999999.9', 'unit' => 'miles'],
                ],
                false, // Field has precision 10, scale 1
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '34', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '27.53', 'unit' => 'miles'],
                ],
                false, // Field has scale 1
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '34', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '-1.2', 'unit' => 'miles'],
                ],
                false,
            ],
            [
                [
                    'distanceTravelledLoaded' => ['value' => '34', 'unit' => 'miles'],
                    'distanceTravelledUnloaded' => ['value' => '2e5', 'unit' => 'miles'],
                ],
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid, ?string $expectedLoadedValue=null, ?string $expectedLoadedUnit=null, ?string $expectedUnloadedValue=null, ?string $expectedUnloadedUnit=null): void
    {
        $data = new DaySummary();

        $form = $this->factory->create(DistanceTravelledType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $loaded = $data->getDistanceTravelledLoaded();
            $this->assertEquals($expectedLoadedValue, $loaded?->getValue());
            $this->assertEquals($expectedLoadedUnit, $loaded?->getUnit());

            $unloaded = $data->getDistanceTravelledUnloaded();
            $this->assertEquals($expectedUnloadedValue, $unloaded?->getValue());
            $this->assertEquals($expectedUnloadedUnit, $unloaded?->getUnit());

        }
    }
}
