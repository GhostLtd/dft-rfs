<?php


namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;


use App\Tests\Ghost\GovUkFrontendBundle\Form\FormTestCase;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Component\Form\FormFactoryInterface;

class ButtonTypeTest extends FormTestCase
{
    public function fixtureProvider()
    {
        $ignoreFixtures = function($fixture) {
            return (isset($fixture['options']['href']) ||
                ($fixture['options']['element'] ?? 'button') !== 'button');
        };
        return $this->loadFixtures('button', $ignoreFixtures);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testButtonFixtures($fixture)
    {
        self::bootKernel();

        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::$container->get('form.factory');

        // create a button form element
        $buttonForm = $formFactory->create(
            ButtonType::class,
            null,
            $this->mapJsonOptions($fixture['options'] ?? []));

        $this->renderAndCompare($fixture['html'], $buttonForm);
    }

    protected function mapJsonOptions($fixtureOptions)
    {
        // All of the options we want to support in ButtonType
        $mappedOptions = ['disabled', 'text', 'html', 'preventDoubleClick', 'classes', 'attributes', 'value'];
        $fixtureOptions = array_intersect_key($fixtureOptions, array_fill_keys($mappedOptions, 0));

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'preventDoubleClick' :
                    $formOptions['prevent_double_click'] = $value;
                    break;
                case 'value' :
                    $formOptions['attr']['value'] = $value;
                    break;
            }
        }

        return $formOptions;
    }
}