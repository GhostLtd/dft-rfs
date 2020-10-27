<?php


namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;


use App\Tests\Ghost\GovUkFrontendBundle\Form\FormTestCase;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;

class ChoiceTypeTest extends FormTestCase
{
    public function checkboxFixtureProvider()
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
        return $this->loadFixtures('checkboxes', $ignoreTests);
    }

    /**
     * @dataProvider checkboxFixtureProvider
     * @param $fixture
     */
    public function testCheckboxesFixtures($fixture)
    {
        $buttonForm = $this->createAndTestForm(
            ChoiceType::class,
            $this->getCheckboxData($fixture['options']['items'] ?? []),
            array_merge([
                'expanded' => true,
                'multiple' => true,
            ],
            $this->mapJsonOptions($fixture['options'] ?? [])),
            $fixture
        );
    }

    protected function getCheckboxData($items = [])
    {
        $data = [];
        foreach ($items as $item)
        {
            if ($item['checked'] ?? false) $data[] = $item['value'];
        }
        return $data;
    }



    public function radioFixtureProvider()
    {
        $ignoreTests = [
            'with a divider',
            'with conditional items',
            'inline with conditional items',
            'with conditional item checked',
            'with optional form-group classes showing group error',
            'small with conditional reveal',
            'small with a divider',
            'fieldset with describedBy',
            'with hints on parent and items', // options without values/labels
            'fieldset params',
        ];
        return $this->loadFixtures('radios', $ignoreTests);
    }

    /**
     * @dataProvider radioFixtureProvider
     * @param $fixture
     */
    public function testRadioFixtures($fixture)
    {
        $this->createAndTestForm(
            ChoiceType::class,
            $this->getRadioData($fixture['options']['items'] ?? []),
            array_merge([
                'expanded' => true,
            ], $this->mapJsonOptions($fixture['options'] ?? [])),
            $fixture
        );
    }

    protected function getRadioData($items = [])
    {
        foreach ($items as $item)
        {
            if ($item['checked'] ?? false) return $item['value'];
        }
    }

    protected function mapJsonOptions($fixtureOptions)
    {
        // All of the options we want to support in ChoiceType
        $mappedOptions = ['items', 'fieldset', 'hint', 'classes', 'attributes', 'formGroup'];
        $fixtureOptions = array_intersect_key($fixtureOptions, array_fill_keys($mappedOptions, 0));

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'items' :
                    $formOptions['choices'] = [];
                    foreach($value as $item) {
                        if ($item['text'] ?? $item['html'] ?? false)
                        {
                            $formOptions['choices'][$item['text'] ?? $item['html']] = $item['value'];
                            if ($item['hint']['text'] ?? false) {
                                $formOptions['choice_options'][$item['text']]['help'] = $item['hint']['text'];
                            }
                            if ($item['disabled'] ?? false) {
//                                $formOptions['choice_attr'][$item['text']]['disabled'] = $item['disabled'];
                                $formOptions['choice_options'][$item['text']]['disabled'] = $item['disabled'];
                            }
                            if ($item['attributes'] ?? false) {
                                $formOptions['choice_attr'][$item['text']] = $item['attributes'];
                            }
                            if ($item['label'] ?? false) {
                                $formOptions['choice_options'][$item['text']]['label_attr'] = $item['label']['attributes'] ?? [];
                                $formOptions['choice_options'][$item['text']]['label_attr']['class'] = $item['label']['classes'] ?? '';

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
            }
        }

        return $formOptions;
    }
}