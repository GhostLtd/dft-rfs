<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;

class InputTypeTest extends AbstractGovUkFrontendTest
{
    public function fixtureProvider(): array
    {
        $ignoreFixtures = [
            'with prefix with attributes',
            'with suffix with attributes',
            'with prefix with classes',
            'with suffix with classes',
        ];

        return $this->loadFixtures('input', $ignoreFixtures);
    }

    /**
     * @dataProvider fixtureProvider
     * @param $fixture
     */
    public function testInputFixtures($fixture): void
    {
        $this->createAndTestForm(
            $this->getInputType($fixture),
            $fixture['options']['value'] ?? null,
            $this->mapJsonOptions($fixture['options'] ?? []),
            $fixture
        );
    }

    protected function getInputType($fixture): string
    {
        if (
            ($fixture['options']['type'] ?? false) === 'number' ||
            !empty($fixture['options']['inputmode'])
        ) {
            return NumberType::class;
        } else {
            return InputType::class;
        }
    }

    #[\Override]
    protected function mapJsonOptions($fixtureOptions): array
    {
        // All of the options we want to support in TextareaType
        $ignoredOptions = [];
        $fixtureOptions = array_diff_key($fixtureOptions, array_fill_keys($ignoredOptions, 0));

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        $formOptions['csrf_protection'] = false;

        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'suffix' :
                    $formOptions['suffix'] = $value['text'] ?? $value['html'] ?? false;
                    $formOptions['suffix_html'] = !empty($value['html']);
                    break;
                case 'prefix' :
                    $formOptions['prefix'] = $value['text'] ?? $value['html'] ?? false;
                    $formOptions['prefix_html'] = !empty($value['html']);
                    break;
                case 'inputmode' :
                    $formOptions['is_decimal'] = true;
                    break;
            }
        }

        return $formOptions;
    }
}
