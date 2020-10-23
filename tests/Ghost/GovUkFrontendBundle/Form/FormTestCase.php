<?php


namespace App\Tests\Ghost\GovUkFrontendBundle\Form;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
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
     */
    protected function assertStructuresMatch(Crawler $expected, Crawler $actual)
    {
        // check the nodes have the same name and number of children
        $this->assertEquals($expected->nodeName(), $actual->nodeName());
        $this->assertEquals($expected->children()->count(), $actual->children()->count());

        // get the text for this node only (by subtracting the text for its children), and check they're the same
        $expectedNodeText = str_replace($expected->children()->text('', true), '', $expected->text(null, true));
        $actualNodeText = str_replace($actual->children()->text('', true), '', $actual->text(null, true));
        $this->assertEquals($expectedNodeText, $actualNodeText);

        // Check the attributes are the same
        $ignoreAttributes = ['id', 'name', 'for', 'aria-describedby'];
        foreach($expected->getNode(0)->attributes as $attributeIndex => $expectedAttribute) {
            if (in_array($expectedAttribute->name, $ignoreAttributes)) continue;
            /** @var \DOMAttr $expectedAttribute */
            $actualAttribute = $actual->attr($expectedAttribute->name);
            $this->assertEquals($expectedAttribute->value, $actualAttribute);
        }

        // traverse the tree of $expected
        $actualChildren = $actual->children();
        $expectedChildren = $expected->children();

        for($childIndex = 0; $childIndex < $expectedChildren->count(); $childIndex++)
        {
            $actualChild = $actualChildren->eq($childIndex);
            $expectedChild = $expectedChildren->eq($childIndex);
            $this->assertStructuresMatch($expectedChild, $actualChild);
        }
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
     * @param $fixtureHtml
     * @param FormInterface $componentForm
     */
    protected function renderAndCompare($fixtureHtml, FormInterface $componentForm)
    {
        /** @var Environment $twig */
        $twig = self::$container->get('twig');

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
        $fixtureCrawler->addHtmlContent($fixtureHtml);

        $renderCrawler = new Crawler();
        $renderCrawler->addHtmlContent($renderedHtml);

        // Select the children of the body elements (ie the content of the fixture/what we've rendered)
        // and assert they're the same
        $this->assertStructuresMatch(
            $fixtureCrawler->filter('body')->children(),
            $renderCrawler->filter('body')->children()
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
                case 'html' :
                    $formOptions['label'] = $value;
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

                default :
                    break;
            }
        }

        return $formOptions;
    }
}