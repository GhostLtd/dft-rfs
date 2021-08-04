<?php


namespace App\Tests\Ghost\GovUkFrontendBundle\Form;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class FormTestCase extends WebTestCase
{
    /**
     * Assert that the two Crawlers have the same content
     * @param Crawler $expected
     * @param Crawler $actual
     * @param string $fixtureName
     */
    protected function assertStructuresMatch(Crawler $expected, Crawler $actual, $fixtureName = '')
    {
        // check the nodes have the same name and number of children
        $this->assertEquals(
            $expected->nodeName(),
            $actual->nodeName(),
            "{$fixtureName}: {$actual->nodeName()}.{$actual->attr('class')}"
        );
        $this->assertEquals(
            $expected->children()->count(),
            $actual->children()->count(),
            "{$fixtureName}: {$actual->nodeName()}.{$actual->attr('class')}"
        );

        // get the text for this node only (by subtracting the text for its children), and check they're the same
        $expectedNodeText = trim(str_replace($expected->children()->text('', true), '', $expected->text(null, true)));
        $actualNodeText = trim(str_replace($actual->children()->text('', true), '', $actual->text(null, true)));
        $this->assertEquals(
            $expectedNodeText,
            $actualNodeText,
            "{$fixtureName}: {$actual->nodeName()}.{$actual->attr('class')}"
        );

        // Check the attributes are the same
        $ignoreAttributes = ['id', 'name', 'for', 'aria-describedby'];
        foreach($expected->getNode(0)->attributes as $attributeIndex => $expectedAttribute) {
            if (in_array($expectedAttribute->name, $ignoreAttributes)) continue;
            /** @var \DOMAttr $expectedAttribute */
            $actualAttribute = $actual->attr($expectedAttribute->name);
            $this->assertEquals(
                $expectedAttribute->value,
                $actualAttribute,
                "{$fixtureName}: {$actual->nodeName()}[{$expectedAttribute->name}={$expectedAttribute->value}]"
            );
        }

        // traverse the tree of $expected
        $actualChildren = $actual->children();
        $expectedChildren = $expected->children();

        for($childIndex = 0; $childIndex < $expectedChildren->count(); $childIndex++)
        {
            $actualChild = $actualChildren->eq($childIndex);
            $expectedChild = $expectedChildren->eq($childIndex);
            $this->assertStructuresMatch($expectedChild, $actualChild, $fixtureName);
        }
    }


    protected function createAndTestForm($formClass, $formData, $formOptions, $fixture)
    {
        self::bootKernel();

        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::$container->get('form.factory');

        // create the form
        $form = $formFactory->create($formClass, $formData, $formOptions);

        if ($fixture['options']['errorMessage'] ?? false) {
            $form->addError(new FormError($fixture['options']['errorMessage']['text']));
        }

        $this->renderAndCompare($fixture, $form);
    }


    /**
     * @param $component string the name of the component
     * @param $ignoreTests array | callable which tests should be ignored
     * @return mixed
     */
    protected function loadFixtures(string $component, $ignoreTests)
    {
        $file = __DIR__ . "/../../../../node_modules/govuk-frontend/govuk/components/${component}/fixtures.json";
        $fixtures = json_decode(file_get_contents($file), true);

        $this->assertEquals($component, $fixtures['component']);
        $fixtures = $fixtures['fixtures'];

        foreach ($fixtures as $index => $fixture)
        {
            if (
                (is_array($ignoreTests) && in_array($fixture['name'] ?? '', $ignoreTests)) ||
                (is_callable($ignoreTests) && $ignoreTests($fixture))
            ) {
                // ignore this test
                unset($fixtures[$index]);
            } else {
                // wrap this test in an array, so it can be used in @dataProvider
                $fixtures[$index] = [$fixture];
            }
        }

        return $fixtures;
    }

    /**
     * @param $fixture
     * @param FormInterface $componentForm
     */
    private function renderAndCompare($fixture, FormInterface $componentForm)
    {
        /** @var Environment $twig */
        $twig = self::$container->get('twig');

        $renderedHtml = '';
        // render it
        try {
            $renderedHtml = $twig->render($twig->createTemplate("{{ form_row(form) }}"), ['form' => $componentForm->createView()]);
        } catch (LoaderError $e) {
            $this->fail($e);
        } catch (RuntimeError $e) {
            $this->fail($e);
        } catch (SyntaxError $e) {
            $this->fail($e);
        }

        // compare results
        $fixtureCrawler = new Crawler();
        $fixtureCrawler->addHtmlContent($fixture['html']);

        $renderCrawler = new Crawler();
        $renderCrawler->addHtmlContent($renderedHtml);

        // Select the children of the body elements (ie the content of the fixture/what we've rendered)
        // and assert they're the same
        $this->assertStructuresMatch(
            $fixtureCrawler->filter('body')->children(),
            $renderCrawler->filter('body')->children(),
            $fixture['name']
        );
    }

    /**
     * Map some common fixture options
     *
     * @param $fixtureOptions
     * @return array
     */
    protected function mapJsonOptions($fixtureOptions)
    {
        $formOptions = ['attr' => [], 'label' => false];
        foreach ($fixtureOptions as $option => $value)
        {
            switch ($option)
            {
                case 'text' :
                    $formOptions['label'] = $value;
                case 'html' :
                    $formOptions['label'] = $value;
                    $formOptions['label_html'] = true;
                    break;
                case 'label' :
                    $formOptions['label'] = $value['text'] ?? $value['html'] ?? null;
                    $formOptions['label_html'] = !empty($value['html']);
                    if ($value['isPageHeading'] ?? false) $formOptions['label_is_page_heading'] = true;
                    break;
                case 'classes' :
                    $formOptions['attr']['class'] = trim(($formOptions['attr']['class'] ?? "") . " " . $value);
                    break;
                case 'attributes' :
                    $formOptions['attr'] = array_merge($formOptions['attr'], $value);
                    break;
                case 'disabled' :
                    $formOptions[$option] = $value;
                    break;
                case 'hint' :
                    $formOptions['help'] = $value['text'] ?? null;
                    break;
                case 'formGroup' :
                    if ($value['classes'] ?? false) {
                        $formOptions['row_attr']['class'] = trim(($formOptions['row_attr']['class'] ?? "") . " " . $value['classes']);
                    }
                    break;
                case 'autocomplete' :
                    $formOptions['attr']['autocomplete'] = $value;
                    break;
                case 'spellcheck' :
                    $formOptions['attr']['spellcheck'] = $value ? 'true' : 'false';
                    break;

                default :
                    break;
            }
        }

        return $formOptions;
    }
}