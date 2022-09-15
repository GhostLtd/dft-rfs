<?php

namespace App\Tests\Functional\Wizard;

class WizardStepUrlTestCase implements WizardTestCase
{
    protected string $expectedUrl;
    protected ?string $submitButtonId;

    /** @var FormTestCase[]|array */
    protected array $formTestCases;

    public function __construct(string $expectedUrl, string $submitButtonId = null, array $formTestCases = [])
    {
        $this->expectedUrl = $expectedUrl;
        $this->submitButtonId = $submitButtonId;
        $this->formTestCases = $formTestCases;
    }

    public function getExpectedTitle(): ?string
    {
        return null;
    }

    public function getExpectedUrl(): string
    {
        return $this->expectedUrl;
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