<?php

namespace App\Tests\NewFunctional\Wizard\Action;

use App\Tests\NewFunctional\Wizard\Form\AbstractFormTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DomCrawler\Crawler;

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

    protected function getErrors(AbstractBrowser $client): array
    {
        $nodes = $client->getCrawler()
            ->filterXPath("//ul[contains(concat(' ',normalize-space(@class),' '),' govuk-error-summary__list ')]/li/a");

        $errors = [];
        foreach ($nodes->each(fn(Crawler $node) => [$node->attr('href'), $node->text()]) as [$href, $text]) {
            $errors[$href] = $text;
        }
        return $errors;
    }

    /**
     * The form exactly as the server first rendered it.
     *
     * Every test case after the first must be submitted against this original state. Re-requesting
     * the page is NOT equivalent: the wizard persists partially-valid submitted data into its session
     * state even when validation fails, so a fresh GET comes back with the previous test case's values
     * already filled in - which silently turns a "this field is required" assertion into a pass.
     */
    protected function captureOriginalForm(AbstractBrowser $client): array
    {
        return [$client->getInternalResponse()->getContent(), $client->getInternalRequest()->getUri()];
    }

    /**
     * The crawler that test case $testCaseIdx's form should be built from: the page as it stands
     * for the first test case, and a fresh parse of the original render for every one after it.
     *
     * (A DomCrawler Form writes its values back into the nodes it was built from, hence a fresh
     * parse rather than reusing one crawler)
     */
    protected function getFormCrawler(AbstractBrowser $client, ?array $originalForm, int $testCaseIdx): Crawler
    {
        if ($testCaseIdx === 0 || $originalForm === null) {
            return $client->getCrawler();
        }

        [$html, $uri] = $originalForm;
        return new Crawler($html, $uri);
    }

    /**
     * Applies radio and checkbox selections straight to the DOM, and drops those fields from the
     * data handed to Form::setValues().
     *
     * DomCrawler registers each checkbox of a `name[]` group as its own single-valued field, so
     * setting the group by its name is an "Unreachable field" error; and a choice field rejects ''
     * outright, whereas the tests use ''/false/null to mean "leave this unselected".
     *
     * As with Form::setValues(), true ticks a single checkbox whatever its value (one without a value
     * attribute submits "on"). For a radio or checkbox group it would be ambiguous, so give the value(s).
     */
    protected function applyChoiceSelections(Crawler $submitButtonNode, array $formSubmissionData): array
    {
        $form = $submitButtonNode->closest('form');

        if ($form === null) {
            return $formSubmissionData;
        }

        foreach ($formSubmissionData as $name => $value) {
            $choices = $form->filterXPath(
                './/input[@type="radio" or @type="checkbox"][@name=' . $this->xpathLiteral($name) . ' or @name=' . $this->xpathLiteral($name . '[]') . ']'
            );

            if ($choices->count() === 0) {
                continue;
            }

            // What a ticked choice submits - a checkbox or radio without a value attribute submits "on"
            $choiceValue = fn(\DOMNode $node) => $node instanceof \DOMElement && $node->hasAttribute('value') ? $node->getAttribute('value') : 'on';
            $available = array_map($choiceValue, iterator_to_array($choices));

            if ($value === true) {
                if ($choices->count() !== 1 || $choices->attr('type') !== 'checkbox') {
                    throw new \InvalidArgumentException(sprintf('Input "%s" is not a single checkbox, so true is ambiguous - give the value(s) to select (possible values: "%s").', $name, join('", "', $available)));
                }
                // Taken as-is, rather than filtered below: the checkbox's own value may be ''
                $wanted = [$available[0]];
            } else {
                // false, null, '' and [] all mean "leave this field unselected".
                $wanted = array_filter(array_map('strval', (array) $value), fn(string $v) => $v !== '');
            }

            // Keep the strictness of Form::setValues(), which rejects a value that none of the choices have
            $unavailable = array_diff($wanted, $available);
            if (!empty($unavailable)) {
                throw new \InvalidArgumentException(sprintf('Input "%s" cannot take "%s" as a value (possible values: "%s").', $name, join('", "', $unavailable), join('", "', $available)));
            }

            foreach ($choices as $node) {
                if (!$node instanceof \DOMElement) {
                    continue;
                }

                if (in_array($choiceValue($node), $wanted, true)) {
                    $node->setAttribute('checked', 'checked');
                } else {
                    $node->removeAttribute('checked');
                }
            }

            unset($formSubmissionData[$name]);
        }

        return $formSubmissionData;
    }

    /**
     * Fails if the form submission produced a server error.
     *
     * This is an assertion rather than a conditional fail() so that it counts once per submission,
     * which keeps per-test assertion counts predictable across runs.
     */
    protected function assertSubmissionDidNotError(Context $context, int $testCaseIdx): void
    {
        $client = $context->getClient();
        $statusCode = $client->getInternalResponse()->getStatusCode();

        // (No exception detail to add: the test env runs with APP_DEBUG=false, so the error page doesn't carry it.
        //  The exception is in var/log/test.log)
        $context->getTestCase()->assertLessThan(
            500,
            $statusCode,
            "Action[{$context->get('_actionIndex')}]/Form[{$testCaseIdx}]: form submission returned HTTP {$statusCode}"
        );
    }

    /**
     * Describes the form on the current page, for use when a submit button or field could not be
     * found. Both the field names and the button ids are listed, since either may be what was mistyped.
     */
    protected function describeForm(AbstractBrowser $client): string
    {
        $fields = [];

        $described = $client->getCrawler()->filterXPath('//input|//select|//textarea')->each(function (Crawler $element) {
            $name = $element->attr('name');

            if ($name === null || str_contains($name, '_token')) {
                return null;
            }

            if (strtolower($element->nodeName()) === 'select') {
                return [$name, $element->children('option')->each(
                    fn(Crawler $option) => $option->attr('value') . ": '" . $option->text() . "'"
                )];
            }

            return in_array($element->attr('type'), ['radio', 'checkbox'], true)
                ? [$name, [$element->attr('value')]]
                : [$name, true];
        });

        foreach (array_filter($described) as [$name, $values]) {
            if (is_array($values)) {
                $fields[$name] = array_merge((array) ($fields[$name] ?? []), $values);
            } else {
                $fields[$name] ??= true;
            }
        }

        $message = "Available form elements:\n";
        foreach ($fields as $name => $values) {
            $message .= "  - $name";
            if (is_array($values)) {
                $message .= " (Choices: " . join(', ', $values) . ")";
            }
            $message .= "\n";
        }

        $buttonIds = array_filter($client->getCrawler()->filterXPath('//button')->each(fn(Crawler $b) => $b->attr('id')));
        $message .= empty($buttonIds)
            ? "Available buttons: (None available)\n"
            : "Available buttons: " . join(', ', $buttonIds) . "\n";

        return $message;
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

        // Only needed to rewind between test cases
        $originalForm = count($formTestCases) > 1 ? $this->captureOriginalForm($client) : null;

        foreach ($formTestCases as $testCaseIdx => $formTestCase) {
            $submitButtonId = $formTestCase->getSubmitButtonId() ?? $wizardSubmitButtonId;

            $formSubmissionData = $formTestCase->getFormData($context);
            $expectedErrorIds = $formTestCase->getExpectedErrorIds($context);

            $this->outputPreFormFillDebug($context, $formSubmissionData, $expectedErrorIds, $testCaseIdx);

            try {
                $submitButtonNode = $this->getFormCrawler($client, $originalForm, $testCaseIdx)
                    ->selectButton($submitButtonId);

                $formSubmissionData = $this->applyChoiceSelections($submitButtonNode, $formSubmissionData);

                $form = $submitButtonNode->form($formSubmissionData, 'POST');
            } catch (\InvalidArgumentException $e) {
                throw new \InvalidArgumentException(
                    "Could not build the form for submit button #{$submitButtonId}: {$e->getMessage()}\n" . $this->describeForm($client),
                    0,
                    $e
                );
            }

            $client->submit($form, []);

            $this->assertSubmissionDidNotError($context, $testCaseIdx);

            $this->checkErrors($context, $testCaseIdx, array_flip($expectedErrorIds));

            if (!$formTestCase->getSkipPageUrlChangeCheck()) {
                if (empty($expectedErrorIds)) {
                    // A step that does not advance has almost always been rejected by validation, and
                    // checkErrors() stays quiet when nothing was expected - so name the errors here
                    $unexpectedErrors = $this->getErrors($client);
                    $testCase->assertPathNotMatches(
                        $expectedPath,
                        $expectedPathIsRegex,
                        'Page path did not change when expected' . (empty($unexpectedErrors)
                            ? ' (no validation errors on the page)'
                            : ', unexpected validation errors: ' . json_encode($unexpectedErrors))
                    );
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
    }

    /**
     * Quotes a string for use as an XPath literal, including strings containing both quote types.
     */
    protected function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'{$value}'";
        }

        if (!str_contains($value, '"')) {
            return "\"{$value}\"";
        }

        return "concat('" . str_replace("'", "', \"'\", '", $value) . "')";
    }
}
