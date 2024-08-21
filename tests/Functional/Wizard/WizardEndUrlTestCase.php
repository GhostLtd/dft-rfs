<?php

namespace App\Tests\Functional\Wizard;

class WizardEndUrlTestCase implements WizardTestCase
{
    public function __construct(protected string $expectedUrl)
    {
    }

    public function getExpectedUrl(): string
    {
        return $this->expectedUrl;
    }
}