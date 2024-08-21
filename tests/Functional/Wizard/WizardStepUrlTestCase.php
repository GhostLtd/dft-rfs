<?php

namespace App\Tests\Functional\Wizard;

class WizardStepUrlTestCase implements WizardTestCase
{
    public function __construct(
        protected string $expectedUrl,
        protected ?string $submitButtonId = null,
        /** @var FormTestCase[]|array */
        protected array $formTestCases = []
    )
    {
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