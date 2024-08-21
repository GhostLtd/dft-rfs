<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class OperatorGroupNameNotPrefixOfExisting extends Constraint
{
    public string $message = "operator-groups.name.prefix";

    public function __construct(
        string $message = null,
        array $groups = [],
        mixed $payload = null,
    )
    {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
    }

    #[\Override]
    public function validatedBy() : string {
        return static::class.'Validator';
    }
}
