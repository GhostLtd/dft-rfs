<?php

namespace App\Tests\NewFunctional\Wizard\Action;

use App\Tests\NewFunctional\Wizard\Form\AbstractFormTestCase;

class FormTestCallbackAction extends AbstractFormTestAction
{
    protected $formTestCallback;

    public function __construct(string $expectedPath, string $submitButtonId = null, ?callable $formTestCallback = null, array $options = [])
    {
        parent::__construct($expectedPath, $submitButtonId, $options);
        $this->formTestCallback = $formTestCallback ?? fn(Context $c) => [];
    }

    /**
     * @return AbstractFormTestCase[]|array
     */
    public function getFormTestCases(Context $context): array
    {
        return ($this->formTestCallback)($context);
    }

    #[\Override]
    public function perform(Context $context): void
    {
        $this->performFormTestAction($context, $this->getFormTestCases($context));
    }
}