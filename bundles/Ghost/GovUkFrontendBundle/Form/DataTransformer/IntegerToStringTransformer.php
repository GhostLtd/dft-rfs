<?php

namespace Ghost\GovUkFrontendBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Symfony's IntegerToLocalizedStringTransformer is *too* clever; I don't want inputs such as "10e8" to be valid input.
 * This transformer only understands integer numbers with an optional "-" prefix.
 */
class IntegerToStringTransformer implements DataTransformerInterface
{
    public function __construct(
        protected string $messageInvalid = 'common.number.invalid',
    ) {}

    /**
     * @return mixed
     */
    public function transform($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!preg_match('#^-?\d+$#', $value, $matches)) {
            throw new TransformationFailedException('Invalid integer amount', 0, null, $this->messageInvalid);
        }

        return (string) $value;
    }

    /**
     * @return mixed
     */
    public function reverseTransform($value)
    {
        if ($value === null || '' === trim($value)) {
            return null;
        }

        if (!preg_match('#^-?\d+$#', $value, $matches)) {
            throw new TransformationFailedException('Invalid integer amount', 0, null, $this->messageInvalid);
        }

        return intval($value);
    }
}
