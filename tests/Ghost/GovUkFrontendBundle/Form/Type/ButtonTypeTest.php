<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;

class ButtonTypeTest extends AbstractGovUkFrontendTest
{
    public function fixtureProvider(): array
    {
        $ignoreFixtures = fn($fixture) =>
            isset($fixture['options']['href']) ||
            ($fixture['options']['element'] ?? 'button') !== 'button';

        return $this->loadFixtures('button', $ignoreFixtures);
    }

    /**
     * @dataProvider fixtureProvider
     * @param $fixture
     */
    public function testButtonFixtures($fixture): void
    {
        $this->createAndTestForm(
            ButtonType::class,
            null,
            $this->mapJsonOptions($fixture['options'] ?? []),
            $fixture
        );
    }

    #[\Override]
    protected function mapJsonOptions($fixtureOptions): array
    {
        // All of the options we want to support in ButtonType
        $mappedOptions = ['disabled', 'text', 'html', 'isStartButton', 'preventDoubleClick', 'classes', 'attributes', 'value', 'type'];
        $fixtureOptions = array_intersect_key($fixtureOptions, array_fill_keys($mappedOptions, 0));

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'preventDoubleClick' :
                    $formOptions['prevent_double_click'] = $value;
                    break;
                case 'isStartButton':
                    $formOptions['is_start_button'] = $value;
                    break;
                case 'value' :
                    $formOptions['attr']['value'] = $value;
                    break;
                case 'type' :
                    $formOptions['type'] = $value;
                    break;
            }
        }

        return $formOptions;
    }
}
