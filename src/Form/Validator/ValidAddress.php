<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
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

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}