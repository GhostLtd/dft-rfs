<?php

namespace App\Tests\NewFunctional\Wizard\Action;

interface WizardAction
{
    public function setName(string $name): self;
    public function getName(): ?string;

    public function perform(Context $context): void;
}