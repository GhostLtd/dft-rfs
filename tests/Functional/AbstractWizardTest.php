<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractWizardTest extends AbstractFrontendFunctionalTest
{
    protected function getBrowserLoadFixturesAndLogin(array $fixtures)
    {
        $browser = $this->getBrowserAndLoadFixtures($fixtures);
        $this->login($browser);

        return $browser;
    }

    protected function doWizardTest(KernelBrowser $browser, array $wizardData)
    {
        foreach ($wizardData as list($expectedTitle, $formButton, $expectedErrors, $formData)) {
            $crawler = $browser->getCrawler();

            if ($expectedTitle !== null) {
                $node = $crawler->filterXPath('//h1');

                if ($node->count() == 0) {
                    $node = $crawler->filterXPath("//legend[contains(concat(' ',normalize-space(@class),' '),' govuk-fieldset__legend ')]")->first();
                }

                $actualTitle = $node->text();
                $this->assertEquals($expectedTitle, $actualTitle, 'Page title as expected');
            }

            if (!empty($expectedErrors)) {
                $this->submitForm($formButton, $browser);
                $this->checkErrors($browser->getCrawler(), array_flip($expectedErrors));
            }

            $this->submitForm($formButton, $browser, $formData);
        }
    }

    protected function checkErrors(Crawler $crawler, array $expectedErrors)
    {
        if (empty($expectedErrors)) {
            return;
        }

        $actualErrors = $this->getErrors($crawler);
        $missingErrors = [];
        $excessErrors = [];

        foreach ($expectedErrors as $k => $v) {
            if (!isset($actualErrors[$k])) {
                $missingErrors[] = $k;
            }
        }

        foreach ($actualErrors as $k => $v) {
            if (!isset($expectedErrors[$k])) {
                $excessErrors[] = $k;
            }
        }

        // Show the difference between excess and missing errors in order to show both at the same time
        // Passing test would expect these both to be empty
        // excess fields cannot occur in missing fields, and vice-versa
        // "tricksy as f#$k" - Mark Allman, 2021-06-03
        $this->assertEquals($excessErrors, $missingErrors, 'Missing/excess validation errors on these fields');
    }

    protected function getErrors(Crawler $crawler): array
    {
        $nodes = $crawler->filterXPath("//ul[contains(concat(' ',normalize-space(@class),' '),' govuk-error-summary__list ')]/li/a");

        $errors = [];
        foreach ($nodes->extract(['_text', 'href']) as $v) {
            $errors[$v[1]] = $v[0];
        }
        return $errors;
    }

    protected function submitForm($formButton, KernelBrowser $browser, $formData = []): void
    {
        $buttonNode = $browser->getCrawler()->selectButton($formButton);
        $this->assertEquals(1, $buttonNode->count(), "Unable to fetch button - '$formButton'");
        $form = $buttonNode->form($formData, 'POST');
        $browser->submit($form, [], []);
    }
}