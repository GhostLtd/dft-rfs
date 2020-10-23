<?php


namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;


use App\Tests\Ghost\GovUkFrontendBundle\Form\FormTestCase;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;

class ChoiceTypeTest extends FormTestCase
{
    public function testFixtureProvider()
    {
        $ignoreTests = [
            'with conditional items',
            'with conditional item checked',
            'with optional form-group classes showing group error',
            'small with conditional reveal',
            'with label classes', // we can't easily do choice label attributes
            'multiple hints', // this one is stupid, it expects there to be an option with no label or value, but has a hint
            'label with attributes', // we can't easily do choice label attributes
            'fieldset params',
        ];

        $fixtures = $this->loadFixtures('checkboxes');
        foreach ($fixtures as $index => $fixture) {
            if (in_array($fixture['name'] ?? '', $ignoreTests)) {
                unset($fixtures[$index]);
            } else {
                $fixtures[$index] = [$fixture];
            }
        }
        return $fixtures;
    }

    /**
     * @dataProvider testFixtureProvider
     */
    public function testCheckboxesFixtures($fixture)
    {
        self::bootKernel();
        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::$container->get('form.factory');

        // create a button form element
        $buttonForm = $formFactory->create(
            ChoiceType::class,
            $this->getData($fixture['options']['items'] ?? []),
            array_merge([
                'expanded' => true,
                'multiple' => true,
            ],
            $this->mapJsonOptions($fixture['options'] ?? []))
        );

        if ($fixture['options']['errorMessage'] ?? false) {
            $buttonForm->addError(new FormError($fixture['options']['errorMessage']['text']));
        }

        $this->renderAndCompare($fixture['html'], $buttonForm);
    }

    protected function getData($items = [])
    {
        $data = [];
        foreach ($items as $item)
        {
            if ($item['checked'] ?? false) $data[] = $item['value'];
        }
        return $data;
    }


    protected function mapJsonOptions($fixtureOptions)
    {
        $mappedOptions = ['items', 'fieldset', 'hint', 'classes', 'attributes'];
        $fixtureOptions = array_intersect_key($fixtureOptions, array_fill_keys($mappedOptions, 0));

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'items' :
                    $formOptions['choices'] = [];
                    foreach($value as $item) {
                        if ($item['text'] ?? false)
                        {
                            $formOptions['choices'][$item['text']] = $item['value'];
                            if ($item['hint']['text'] ?? false) {
                                $formOptions['choice_help'][$item['text']] = $item['hint']['text'];
                            }
                            if ($item['disabled'] ?? false) {
                                $formOptions['choice_options'][$item['text']]['disabled'] = $item['disabled'];
                            }
                            if ($item['attributes'] ?? false) {
                                $formOptions['choice_options'][$item['text']] = $item['attributes'];
                            }
                        }
                    }
                    break;

                case 'fieldset' :
                    $formOptions['label'] = $value['legend']['text'] ?? $value['legend']['html'] ?? false;
                    if ($value['legend']['classes'] ?? false)
                    {
                        $formOptions['label_attr'] = $formOptions['label_attr'] ?? [];
                        $formOptions['label_attr']['class'] = trim(($formOptions['label_attr']['class'] ?? "") . " {$value['legend']['classes']}");
                    }
                    $formOptions['label_is_page_heading'] = $value['legend']['isPageHeading'] ?? false;
                    break;

                case 'hint' :
                    $formOptions['help'] = $value['text'] ?? null;
                    break;
            }
        }

//        dump($formOptions); exit;
        return $formOptions;
    }
}