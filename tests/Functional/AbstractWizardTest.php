<?php

namespace App\Tests\Functional;

use App\Tests\Functional\Wizard\WizardEndTestCase;
use App\Tests\Functional\Wizard\WizardStepTestCase;
use App\Tests\Functional\Wizard\WizardTestCase;
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

    protected function doWizardTest(KernelBrowser $browser, array $wizardTestCases)
    {
        /** @var WizardTestCase $wizardTestCase */
        foreach ($wizardTestCases as $wizardTestCase) {
            $crawler = $browser->getCrawler();

            if ($wizardTestCase instanceof WizardStepTestCase) {
                // A step in a wizard...
                $expectedTitle = $wizardTestCase->getExpectedTitle();

                if ($expectedTitle !== null) {
                    $actualTitle = $this->getTitle($crawler);
                    $this->assertEquals($expectedTitle, $actualTitle, 'Page title as expected');
                }

                $submitButtonId = $wizardTestCase->getSubmitButtonId();
                if ($submitButtonId !== null) {
                    foreach ($wizardTestCase->getFormTestCases() as $formTestCase) {
                        $this->submitForm($submitButtonId, $browser, $formTestCase->getFormData());
                        $this->checkErrors($browser->getCrawler(), array_flip($formTestCase->getExpectedErrorIds()));
                    }
                }
            } else if ($wizardTestCase instanceof WizardEndTestCase) {
                // The end of a wizard
                $expectedTitle = $wizardTestCase->getExpectedTitle();
                $matchMode = $wizardTestCase->getMatchMode();

                $actualTitle = $this->getTitle($crawler);

                if ($matchMode === WizardEndTestCase::MODE_EXACT) {
                    $this->assertEquals($expectedTitle, $actualTitle, 'Page title as expected');
                } else if ($matchMode === WizardEndTestCase::MODE_CONTAINS) {
                    $this->assertStringContainsString($expectedTitle, $actualTitle, 'Page title as expected');
                }
            }
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

    /**
     * @param Crawler $crawler
     * @return string
     */
    protected function getTitle(Crawler $crawler): string
    {
        $node = $crawler->filterXPath('//h1');

        if ($node->count() == 0) {
            $node = $crawler->filterXPath("//legend[contains(concat(' ',normalize-space(@class),' '),' govuk-fieldset__legend ')]")->first();
        }

        $actualTitle = $node->text();
        return $actualTitle;
    }
}