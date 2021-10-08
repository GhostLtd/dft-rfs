<?php

namespace App\Tests\Functional\Wizard;

class FormTestCase
{
    protected array $formData;
    protected array $expectedErrorIds;
    protected ?string $submitButtonId;

    public function __construct(array $formData, array $expectedErrorIds = [], string $submitButtonId = null)
    {
        $this->formData = $formData;
        $this->expectedErrorIds = $expectedErrorIds;
        $this->submitButtonId = $submitButtonId;
    }

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function getExpectedErrorIds(): array
    {
        return $this->expectedErrorIds;
    }

    public function getSubmitButtonId(): ?string
    {
        return $this->submitButtonId;
    }
}