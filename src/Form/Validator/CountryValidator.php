<?php

namespace App\Form\Validator;

use App\Entity\CountryInterface;
use App\Form\CountryType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CountryValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Country) {
            throw new UnexpectedTypeException($constraint, Country::class);
        }

        if (!$value) {
            return;
        }

        if (!$value instanceof CountryInterface) {
            throw new UnexpectedValueException($value, CountryInterface::class);
        }

        $country = $value->getCountry();
        $countryOther = $value->getCountryOther();

        if ($country === null) {
            $this->context->buildViolation($constraint->message)->atPath('country')->addViolation();
        } else if ($country === CountryType::OTHER && ($countryOther === null || $countryOther === '')) {
            $this->context->buildViolation($constraint->otherMessage)->atPath('country_other')->addViolation();
        }
    }
}