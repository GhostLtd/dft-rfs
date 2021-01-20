<?php

namespace Ghost\GovUkFrontendBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DecimalToStringTransformer implements DataTransformerInterface
{
    /** @var string */
    protected $invalidMessage;

    /** @var array */
    protected $invalidMessageParameters;

    /** @var int */
    protected $precision;

    /** @var int */
    protected $scale;

    public function __construct(string $invalidMessage, array $invalidMessageParameters = [], int $precision = 8, int $scale = 2)
    {
        $this->invalidMessage = $invalidMessage;
        $this->invalidMessageParameters = $invalidMessageParameters;
        $this->precision = $precision;
        $this->scale = $scale;
    }

    public function transform($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!preg_match($this->getRegex(), $value, $matches)) {
            throw new TransformationFailedException('Invalid decimal string', 0, null, $this->invalidMessage, $this->invalidMessageParameters);
        }

        if (false !== strpos($value, '.')) {
            $value = rtrim($value, '0');
            return rtrim($value, '.');
        } else {
            return $value;
        }
    }

    public function reverseTransform($value)
    {
        if ('' === trim($value)) {
            return null;
        }

        if (!preg_match($this->getRegex(), $value, $matches)) {
            throw new TransformationFailedException('Invalid decimal amount', 0, null, $this->invalidMessage, $this->invalidMessageParameters);
        }

        $sign = ($matches['sign'] ?? null) === '-' ? -1 : 1;
        $int = $sign * $matches['int'];
        $dec = $matches['dec'] ?? '';

        return $this->scale > 0 ?
            $int.'.'.str_pad($dec, $this->scale, '0') : $int;
    }

    protected function getRegex()
    {
        $scaleRegex = $this->scale > 0 ? ('\d{1,'.$this->scale.'}') : '';
        return '#^(?P<sign>-)?(?P<int>\d{1,'.$this->precision.'}+)(?:\.(?P<dec>'.$scaleRegex.'))?$#';
    }
}
