<?php

namespace App\Tests\NewFunctional\Wizard\Action;

abstract class AbstractAction implements WizardAction
{
    protected ?string $name = null;

    #[\Override]
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[\Override]
    public function getName(): ?string
    {
        return $this->name;
    }

    public function outputHeader(Context $context): void
    {
        $shortClassName = (new \ReflectionClass($this))->getShortName();
        $header = $this->getName() ?? "<$shortClassName>";

        $context->outputHeader($header);
    }
}