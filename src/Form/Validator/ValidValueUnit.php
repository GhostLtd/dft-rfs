<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidValueUnit extends Constraint
{
    public string $unitBlankMessage = "common.value-unit.unit.not-blank";
    public string $valueBlankMessage = "common.value-unit.value.not-blank";
    public string $valuePositiveMessage = "common.value-unit.value.positive-or-zero";
    public bool $allowBlank = false;

    public function __construct(
        string $unitBlankMessage = null,
        string $valueBlankMessage = null,
        string $valuePositiveMessage = null,
        bool   $allowBlank = null,
        array  $groups = [],
        mixed  $payload = null,
    )
    {
        parent::__construct([], $groups, $payload);

        $this->unitBlankMessage = $unitBlankMessage ?? $this->unitBlankMessage;
        $this->valueBlankMessage = $valueBlankMessage ?? $this->valueBlankMessage;
        $this->valuePositiveMessage = $valuePositiveMessage ?? $this->valuePositiveMessage;
        $this->allowBlank = $allowBlank ?? $this->allowBlank;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
