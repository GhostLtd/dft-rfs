<?php

namespace App\Tests\NewFunctional\Wizard\Form;

use App\Tests\NewFunctional\Wizard\Action\Context;

abstract class AbstractFormTestCase
{
    public function __construct(protected array $expectedErrorIds = [], protected ?string $submitButtonId = null, protected bool $skipPageUrlChangeCheck = false)
    {
    }

    public function getExpectedErrorIds(Context $context): array
    {
        return $this->expectedErrorIds;
    }

    public function getSubmitButtonId(): ?string
    {
        return $this->submitButtonId;
    }

    public function getSkipPageUrlChangeCheck()
    {
        return $this->skipPageUrlChangeCheck;
    }

    abstract public function getFormData(Context $context): array;
}