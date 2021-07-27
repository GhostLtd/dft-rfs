<?php

namespace App\Tests\Functional\Wizard;

class WizardStepTestCase implements WizardTestCase
{
    protected string $expectedTitle;
    protected ?string $submitButtonId;

    /** @var FormTestCase[]|array */
    protected array $formTestCases;

    public function __construct(string $expectedTitle, string $submitButtonId = null, array $formTestCases = [])
    {
        $this->expectedTitle = $expectedTitle;
        $this->submitButtonId = $submitButtonId;
        $this->formTestCases = $formTestCases;
    }

    public function getExpectedTitle(): string
    {
        return $this->expectedTitle;
    }

    public function getSubmitButtonId(): ?string
    {
        return $this->submitButtonId;
    }

    /**
     * @return FormTestCase[]|array
     */
    public function getFormTestCases(): array
    {
        return $this->formTestCases;
    }
}