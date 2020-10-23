<?php


namespace App\Tests\Ghost\GovUkFrontendBundle\Form;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

class FormTestCase extends WebTestCase
{
    protected function assertStructuresMatch(Crawler $expected, Crawler $actual)
    {
        $this->assertEquals($expected->nodeName(), $actual->nodeName());
        $this->assertEquals($expected->children()->count(), $actual->children()->count());
        $expectedNodeText = str_replace($expected->children()->text('', true), '', $expected->text(null, true));
        $actualNodeText = str_replace($actual->children()->text('', true), '', $actual->text(null, true));
        $this->assertEquals($expectedNodeText, $actualNodeText);

        // traverse the tree of $expected
        $actualChildren = $actual->children();
        $expectedChildren = $expected->children();

        $ignoreAttributes = ['id', 'name', 'for', 'aria-describedby'];
        foreach($expected->getNode(0)->attributes as $attributeIndex => $expectedAttribute) {
            if (in_array($expectedAttribute->name, $ignoreAttributes)) continue;
            /** @var \DOMAttr $expectedAttribute */
            $actualAttribute = $actual->attr($expectedAttribute->name);
            $this->assertEquals($expectedAttribute->value, $actualAttribute);
        }

        for($childIndex = 0; $childIndex < $expectedChildren->count(); $childIndex++)
        {
            $actualChild = $actualChildren->eq($childIndex);
            $expectedChild = $expectedChildren->eq($childIndex);
            $this->assertStructuresMatch($expectedChild, $actualChild);
        }
    }

    protected function loadFixtures($component)
    {
        $file = __DIR__ . "/../../../../node_modules/govuk-frontend/govuk/components/${component}/fixtures.json";
        $fixtures = json_decode(file_get_contents($file), true);

        $this->assertEquals($component, $fixtures['component']);

        return $fixtures;
    }

    protected function renderAndCompare($fixtureHtml, FormInterface $componentForm)
    {
        /** @var Environment $twig */
        $twig = self::$container->get('twig');

        // render it
        $renderedHtml = $twig->render($twig->createTemplate("{{ form_row(form) }}"), ['form' => $componentForm->createView()]);
//        echo $fixtureHtml . "\n===\n";
//        echo $renderedHtml; exit;

        // compare results
        $fixtureCrawler = new Crawler();
        $fixtureCrawler->addHtmlContent($fixtureHtml);

        $renderCrawler = new Crawler();
        $renderCrawler->addHtmlContent($renderedHtml);

        $this->assertStructuresMatch($fixtureCrawler->filter('body')->children(), $renderCrawler->filter('body')->children());
    }

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
                    $formOptions['attr']['class'] = trim(($formOptions['attr']['class'] ?? "") . " {$value}");
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