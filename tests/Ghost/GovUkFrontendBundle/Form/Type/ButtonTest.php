<?php


namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;


use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\FormFactoryInterface;
use Twig\Environment;

class ButtonTest extends WebTestCase
{
    public function testGovukFrontendFixtures()
    {
        self::bootKernel();
        $container = self::$container;

        /** @var FormFactoryInterface $formFactory */
        $formFactory = $container->get('form.factory');

        /** @var Environment $twig */
        $twig = $container->get('twig');

        $file = __DIR__ . '/../../../../../node_modules/govuk-frontend/govuk/components/button/fixtures.json';
        $fixtures = json_decode(file_get_contents($file), true);

        $this->assertEquals('button', $fixtures['component']);

        foreach ($fixtures['fixtures'] as $fixture)
        {
            // only test true buttons here
            if (stripos($fixture['name'], 'link') === false){
                echo $fixture['name'] . ': ';

                // map the options

                // create a button form element
                $buttonForm = $formFactory->create(ButtonType::class);

                // render it
                $renderedHtml = $twig->render($twig->createTemplate("{{ form_row(form) }}"), ['form' => $buttonForm->createView()]);

                // compare results
                $fixtureCrawler = new Crawler();
                $fixtureCrawler->addHtmlContent($fixture['html']);
                var_dump($fixtureCrawler->filter('body')->first()->children()->first()->nodeName());

            }
        }

    }

    /**
     * @param $htmlAsString
     * @return \DOMNode
     */
    protected function getDomNode($htmlAsString)
    {
        $domDocument = new \DOMDocument();
        $fragment = $domDocument->createDocumentFragment();
        $fragment->appendXML($htmlAsString);
        $domDocument->appendChild($fragment);
        return $domDocument->childNodes[0];
    }

    protected function mapJsonOptions()
    {

    }
}