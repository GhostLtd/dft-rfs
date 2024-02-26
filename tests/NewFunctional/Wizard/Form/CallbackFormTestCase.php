<?php

namespace App\Tests\NewFunctional\Wizard\Form;

use App\Tests\NewFunctional\Wizard\Action\Context;

class CallbackFormTestCase extends AbstractFormTestCase
{
    protected $formDataCallback;

    public function __construct(callable $formDataCallback, array $expectedErrorIds = [], string $submitButtonId = null, $skipPageUrlChangeCheck = false)
    {
        $this->formDataCallback = $formDataCallback;
        parent::__construct($expectedErrorIds, $submitButtonId, $skipPageUrlChangeCheck);
    }

    #[\Override]
    public function getFormData(Context $context): array
    {
        return ($this->formDataCallback)($context);
    }
}