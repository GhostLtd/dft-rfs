<?php


namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;


use App\Tests\Ghost\GovUkFrontendBundle\Form\FormTestCase;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;

class InputTypeTest extends FormTestCase
{
    public function fixtureProvider()
    {
        $ignoreFixtures = function ($fixture) {
            // suffix/prefix not supported
            return preg_match('(suffix|prefix)', $fixture['name']) === 1;
        };
        return $this->loadFixtures('input', $ignoreFixtures);
    }

    /**
     * @dataProvider fixtureProvider
     * @param $fixture
     */
    public function testInputFixtures($fixture)
    {
        $form = $this->createAndTestForm(
            $this->getInputType($fixture),
            $fixture['options']['value'] ?? null,
            $this->mapJsonOptions($fixture['options'] ?? []),
            $fixture
        );
    }

    protected function getInputType($fixture)
    {
        switch (true)
        {
            case ($fixture['options']['type'] ?? false) === 'number' :
            case !empty($fixture['options']['inputmode']) :
                return NumberType::class;
        }
        return InputType::class;
    }

    protected function mapJsonOptions($fixtureOptions)
    {
        // All of the options we want to support in TextareaType
        $ignoredOptions = [];
        $fixtureOptions = array_diff_key($fixtureOptions, array_fill_keys($ignoredOptions, 0));

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'inputmode' :
                    $formOptions['is_decimal'] = true;
                    break;
            }
        }

        return $formOptions;
    }
}