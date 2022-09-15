<?php

namespace App\Tests\Functional\Wizard;

class WizardEndUrlTestCase implements WizardTestCase
{
    protected string $expectedUrl;

    public function __construct(string $expectedUrl)
    {
        $this->expectedUrl = $expectedUrl;
    }

    public function getExpectedUrl(): string
    {
        return $this->expectedUrl;
    }
}