<?php

namespace App\Tests\Functional\Wizard;

class FormTestCase
{
    public function __construct(protected array $formData, protected array $expectedErrorIds = [], protected ?string $submitButtonId = null)
    {
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