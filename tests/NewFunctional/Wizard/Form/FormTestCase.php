<?php

namespace App\Tests\NewFunctional\Wizard\Form;

use App\Tests\NewFunctional\Wizard\Action\Context;

class FormTestCase extends AbstractFormTestCase
{
    public function __construct(protected array $formData, array $expectedErrorIds = [], string $submitButtonId = null, $skipPageUrlChangeCheck = false)
    {
        parent::__construct($expectedErrorIds, $submitButtonId, $skipPageUrlChangeCheck);
    }

    #[\Override]
    public function getFormData(Context $context): array
    {
        return $this->formData;
    }
}