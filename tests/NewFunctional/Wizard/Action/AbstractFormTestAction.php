<?php

namespace App\Tests\NewFunctional\Wizard\Action;

use App\Tests\NewFunctional\Wizard\Form\AbstractFormTestCase;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\ServerExtensionLegacy;

abstract class AbstractFormTestAction extends PathTestAction
{
    public function __construct(string $expectedPath, protected ?string $submitButtonId = null, array $options = [])
    {
        parent::__construct($expectedPath, $options);
    }

    public function getSubmitButtonId(): ?string
    {
        return $this->submitButtonId;
    }

    protected function checkErrors(Context $context, int $testCaseIdx, array $expectedErrors)
    {
        if (empty($expectedErrors)) {
            return;
        }

        $testCase = $context->getTestCase();
        $actionIndex = $context->get('_actionIndex');

        $actualErrors = $this->getErrors($context->getClient());
        $missingErrors = [];
        $excessErrors = [];
        foreach ($expectedErrors as $k => $v) {
            if (!isset($actualErrors[$k])) {
                $missingErrors[] = $k;
            }
        }

        foreach ($actualErrors as $k => $v) {
            $testCase->assertDoesNotMatchRegularExpression('/^[\w\-]+(\.[\w\-]+)+$/', $v, "Action[$actionIndex]/Form[$testCaseIdx]: Error message appears to be un-translated");
            if (!isset($expectedErrors[$k])) {
                $excessErrors[] = $k;
            }
        }

        // Show the difference between excess and missing errors in order to show both at the same time
        // Passing test would expect these both to be empty
        // excess fields cannot occur in missing fields, and vice-versa
        $testCase->assertEquals($missingErrors, $excessErrors, "Action[$actionIndex]/Form[$testCaseIdx]: Missing/excess validation errors on these fields");
    }

    protected function getBrowserSideSavedFormData(Client $client): string
    {
        $attribute = 'data-test-formdata';
        $client->executeScript("document.getElementsByTagName('html')[0].setAttribute('{$attribute}',JSON.stringify(Array.from(new FormData(document.querySelector('form'))).filter(x => !x[0].includes('_token'))))");
        return $client->findElement(WebDriverBy::xpath('/html'))->getAttribute($attribute);
    }

    protected function getErrors(Client $client): array
    {
        $nodes = $client->findElements(WebDriverBy::xpath("//ul[contains(concat(' ',normalize-space(@class),' '),' govuk-error-summary__list ')]/li/a"));

        $errors = [];
        foreach ($nodes as $node) {
            $errors[$node->getAttribute('href')] = $node->getText();
        }
        return $errors;
    }

    protected function restoreFormData(Client $client, string $formData, bool $clearFormBeforeSetting = true): void
    {
        $script = $clearFormBeforeSetting ?
            "document.querySelectorAll('input[type=text],textarea').forEach(e => e.value = '');
             document.querySelectorAll('input[type=checkbox],input[type=radio]').forEach(e => e.checked = false);" :
            "";

        $script .= "
        JSON.parse('$formData').forEach(z => {
            let elem = document.getElementsByName(z[0])[0];
            let type = elem.getAttribute('type');
            if (['checkbox','radio'].includes(type)) {
                elem.checked = z[1];
            } else {
                elem.value = z[1];
            }
        });";

        $client->executeScript($script);
    }

    /**
     * @param array|AbstractFormTestCase[] $formTestCases
     */
    protected function performFormTestAction(Context $context, array $formTestCases): void
    {
        $this->outputHeader($context);

        $wizardSubmitButtonId = $this->getSubmitButtonId();
        if ($wizardSubmitButtonId === null) {
            return;
        }

        $client = $context->getClient();
        $testCase = $context->getTestCase();

        $expectedPath = $this->getResolvedExpectedPath($context);
        $expectedPathIsRegex = $this->isExpectedPathRegex();

        $this->outputPathDebug($context, $expectedPath, $expectedPathIsRegex, 'a.');

        $context->getTestCase()->assertPathMatches($expectedPath, $expectedPathIsRegex);
        $savedFormData = $this->getBrowserSideSavedFormData($client);

        foreach ($formTestCases as $testCaseIdx => $formTestCase) {
            $submitButtonId = $formTestCase->getSubmitButtonId() ?? $wizardSubmitButtonId;

            try {
                $formSubmissionData = $formTestCase->getFormData($context);
                $expectedErrorIds = $formTestCase->getExpectedErrorIds($context);

                $this->outputPreFormFillDebug($context, $formSubmissionData, $expectedErrorIds, $testCaseIdx);

                if ($testCaseIdx > 0) {
                    $this->restoreFormData($client, $savedFormData);
                }

                $submitButtonNode = $client->getCrawler()->selectButton($submitButtonId);

                try {
                    $formSubmissionData = PantherBugWorkaround::clearMentionedMultiSelections($submitButtonNode, $formSubmissionData, $client);

                    // Fill form and submit as per Client->submitForm(), but with a screenshot taken in the middle...
                    $form = $submitButtonNode->form($formSubmissionData, 'POST');
                }
                catch(NoSuchElementException $e) {
                    $formElements = $client->findElements(WebDriverBy::xpath('//input|//select|//textarea'));

                    $formElementNames = [];

                    foreach($formElements as $element) {
                        $tagName = strtolower($element->getTagName());
                        $name = $element->getAttribute('name');
                        $value = $element->getAttribute('value');

                        if ($tagName === 'select') {
                            $options = $element->findElements(WebDriverBy::xpath('option'));
                            $formElementNames[$name] ??= [];

                            foreach($options as $option) {
                                $optionValue = $option->getAttribute('value');
                                $optionName = $option->getText();

                                $formElementNames[$name][] = "{$optionValue}: '{$optionName}'";
                            }
                        } else {
                            $type = $element->getAttribute('type');

                            if (!str_contains($name, '_token')) {
                                if (in_array($type, ['radio', 'checkbox'])) {
                                    $formElementNames[$name] ??= [];
                                    $formElementNames[$name][] = $value;
                                } else {
                                    $formElementNames[$name] ??= true;
                                }
                            }
                        }
                    }

                    $message = "NoSuchElement\nAvailable form elements:\n";
                    foreach($formElementNames as $name => $values) {
                        $message .= "  - $name";
                        if (is_array($values)) {
                            $possibilities = join(', ', $values);
                            $message .= " (Choices: $possibilities)";
                        }
                        $message .= "\n";
                    }

                    throw new \Exception($message, 0, $e);
                }

                $this->outputPostFormFillDebug($context, $testCaseIdx);

                $client->submit($form, []);

                $this->outputPostFormSubmitDebug($context, $testCaseIdx);
            } catch (\InvalidArgumentException $e) {
                $buttons = $client->findElements(WebDriverBy::cssSelector('button'));
                $buttonsWithIds = array_filter(array_map(fn(WebDriverElement $e) => $e->getAttribute('ID'), $buttons), fn($x) => $x !== null);

                $hints = empty($buttonsWithIds) ?
                    "(None available)" :
                    "(Available buttons: ".join(', ', $buttonsWithIds).")";

                throw new \InvalidArgumentException("Unable to fetch button with ID #{$submitButtonId} {$hints}");
            }

            $this->checkErrors($context, $testCaseIdx, array_flip($expectedErrorIds));


            if (!$formTestCase->getSkipPageUrlChangeCheck()) {
                if (empty($expectedErrorIds)) {
                    $testCase->assertPathNotMatches($expectedPath, $expectedPathIsRegex, 'Page path did not change when expected');
                } else {
                    $testCase->assertPathMatches($expectedPath, $expectedPathIsRegex, 'Page path changed unexpectedly');
                }
            }
        }
    }

    protected function outputPreFormFillDebug(Context $context, array $formSubmissionData, array $expectedErrorIds, int $testCaseIdx): void
    {
        $testCaseLetter = chr($testCaseIdx + ord('b'));

        if ($context->isAtLeastDebugLevel(2)) {

            $data = [];
            foreach ($formSubmissionData as $field => $value) {
                $wrap = fn($s) => '"'.$s.'"';
                $value = is_array($value) ? ('[' . join(', ', array_map($wrap, $value)) . ']') : $wrap($value);
                $data[] = "$field=$value";
            }

            $context->outputWithPrefix(
                "<comment>Submit form data  :</comment> " . (empty($data) ? 'None' : join(', ', $data)),
                "{$testCaseLetter}."
            );
            $context->outputWithPrefix(
                "<comment>Expected errors   :</comment> " . (empty($expectedErrorIds) ? 'None' : join(', ', $expectedErrorIds)),
                "  "
            );
        }

        if ($context->isAtLeastDebugLevel(4)) {
            $this->takeDebugScreenshot($context, $testCaseIdx, 'A-pre-fill');
        }
    }

    protected function outputPostFormFillDebug(Context $context, int $testCaseIdx): void
    {
        if ($context->isAtLeastDebugLevel(3)) {
            $this->takeDebugScreenshot($context, $testCaseIdx, 'B-post-fill');
        }
    }

    protected function outputPostFormSubmitDebug(Context $context, int $testCaseIdx): void
    {
        if ($context->isAtLeastDebugLevel(5)) {
            $this->takeDebugScreenshot($context, $testCaseIdx, 'C-post-sub');
        }
    }

    protected function takeDebugScreenshot(Context $context, int $testCaseIdx, string $typeSuffix): void
    {
        $testCaseLetter = chr($testCaseIdx + ord('b'));
        $actionIdx = intval($context->get('_actionIndex')) + 1;

        // Remove filename unsafe characters
        $dataSetName = $context->getTestCase()->getDataSetName();

        if ($dataSetName !== null) {
            $dataSetName = str_replace(array_merge(
                array_map('chr', range(0, 31)),
                ['<', '>', ':', '"', '/', '\\', '|', '?', '*']
            ), '', $dataSetName);
        }

        $context->outputWithPrefix(
            "<comment>Screenshot data   :</comment> {$actionIdx}{$testCaseLetter}-{$typeSuffix}, ".($dataSetName ?? 'no-set'),
            "  "
        );

        // It's deprecated, but at least phpstan can detect the method on the class
        ServerExtensionLegacy::takeScreenshots("{$actionIdx}{$testCaseLetter}-{$typeSuffix}", $dataSetName ?? 'no-set');
    }
}