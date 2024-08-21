<?php

namespace App\Tests\NewFunctional\Wizard\Form;

use App\Tests\NewFunctional\Wizard\Action\Context;

class CallbackIncludingErrorsFormTestCase extends AbstractFormTestCase
{
    protected $formDataCallback;
    protected $expectedErrorCallback;

    public function __construct(callable $formDataCallback, callable $expectedErrorCallback, string $submitButtonId = null, $skipPageUrlChangeCheck = false)
    {
        $this->formDataCallback = $formDataCallback;
        $this->expectedErrorCallback = $expectedErrorCallback;

        parent::__construct([], $submitButtonId, $skipPageUrlChangeCheck);
    }

    #[\Override]
    public function getFormData(Context $context): array
    {
        return ($this->formDataCallback)($context);
    }

    #[\Override]
    public function getExpectedErrorIds(Context $context): array
    {
        return ($this->expectedErrorCallback)($context);
    }
}