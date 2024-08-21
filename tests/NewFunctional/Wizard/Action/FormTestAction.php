<?php

namespace App\Tests\NewFunctional\Wizard\Action;

use App\Tests\NewFunctional\Wizard\Form\AbstractFormTestCase;

class FormTestAction extends AbstractFormTestAction
{
    protected ?string $submitButtonId = null;

    public function __construct(string $expectedPath, string $submitButtonId = null, /** @var AbstractFormTestCase[]|array */
    protected array $formTestCases = [], array $options = [])
    {
        parent::__construct($expectedPath, $submitButtonId, $options);
    }

    #[\Override]
    public function getSubmitButtonId(): ?string
    {
        return $this->submitButtonId;
    }

    /**
     * @return AbstractFormTestCase[]|array
     */
    public function getFormTestCases(): array
    {
        return $this->formTestCases;
    }

    #[\Override]
    public function perform(Context $context): void
    {
        $this->performFormTestAction($context, $this->getFormTestCases());
    }
}