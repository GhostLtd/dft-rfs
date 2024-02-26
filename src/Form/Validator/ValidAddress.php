<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidAddress extends Constraint
{
    public string $line1BlankMessage = "common.address.line-1.not-blank";
    public string $postcodeBlankMessage = "common.address.postcode.not-blank";
    public string $maxLengthMessage = "common.string.max-length";

    public bool $allowBlank = false;
    public bool $validatePostcode = false;

    /**
     * For when the first line is used for company name, and is excluded from a form
     */
    public bool $includeAddressee = true;

    public function __construct(

        string $line1BlankMessage = null,
        string $postcodeBlankMessage = null,
        string $maxLengthMessage = null,
        bool   $allowBlank = null,
        bool   $validatePostcode = null,
        bool   $includeAddressee = null,
        array  $groups = [],
        mixed  $payload = null,
    )
    {
        parent::__construct([], $groups, $payload);

        $this->line1BlankMessage = $line1BlankMessage ?? $this->line1BlankMessage;
        $this->postcodeBlankMessage = $postcodeBlankMessage ?? $this->postcodeBlankMessage;
        $this->maxLengthMessage = $maxLengthMessage ?? $this->maxLengthMessage;
        $this->allowBlank = $allowBlank ?? $this->allowBlank;
        $this->validatePostcode = $validatePostcode ?? $this->validatePostcode;
        $this->includeAddressee = $includeAddressee ?? $this->includeAddressee;
    }


    #[\Override]
    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
