<?php

namespace App\Tests\Functional\Wizard;

class FormTestCase
{
    protected array $formData;
    protected array $expectedErrorIds;

    public function __construct(array $formData, array $expectedErrorIds = [])
    {
        $this->formData = $formData;
        $this->expectedErrorIds = $expectedErrorIds;
    }

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function getExpectedErrorIds(): array
    {
        return $this->expectedErrorIds;
    }
}