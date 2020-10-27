<?php


namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;


use App\Tests\Ghost\GovUkFrontendBundle\Form\FormTestCase;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\FormFactoryInterface;

class TextareaTypeTest extends FormTestCase
{
    public function fixtureProvider()
    {
        $ignoreFixtures = [];
        return $this->loadFixtures('textarea', $ignoreFixtures);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testTextareaFixtures($fixture)
    {
        $form = $this->createAndTestForm(
            TextareaType::class,
            $fixture['options']['value'] ?? null,
            $this->mapJsonOptions($fixture['options'] ?? []),
            $fixture
        );
    }

    protected function mapJsonOptions($fixtureOptions)
    {
        // All of the options we want to support in TextareaType
        $mappedOptions = ['disabled', 'label', 'text', 'html', 'classes', 'attributes', 'hint', 'rows',
            'isPageHeading', 'formGroup', 'autocomplete', 'spellcheck',
        ];
        $fixtureOptions = array_intersect_key($fixtureOptions, array_fill_keys($mappedOptions, 0));

        $formOptions = parent::mapJsonOptions($fixtureOptions);
        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'rows' :
                    $formOptions['rows'] = $value;
                    break;
                case 'autocomplete' :
                    $formOptions['attr']['autocomplete'] = $value;
                    break;
                case 'spellcheck' :
                    $formOptions['attr']['spellcheck'] = $value ? 'true' : 'false';
                    break;
            }
        }

        return $formOptions;
    }
}