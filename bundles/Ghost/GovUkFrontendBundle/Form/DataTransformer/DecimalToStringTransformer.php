<?php

namespace Ghost\GovUkFrontendBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DecimalToStringTransformer implements DataTransformerInterface
{
    public function __construct(
        protected string $messageInvalid = 'common.number.invalid',
        protected string $messageTooLargeWholePart = 'common.number.too-large.whole',
        protected string $messageTooLargeDecimals = 'common.number.too-large.decimal',
        protected int    $precision = 8,
        protected int    $scale = 2
    )
    {}

    /**
     * @return mixed
     */
    public function transform($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        $this->parseNumber($value);

        if (str_contains($value, '.')) {
            $value = rtrim($value, '0');
            return rtrim($value, '.');
        } else {
            return $value;
        }
    }

    /**
     * @return mixed
     */
    public function reverseTransform($value)
    {
        if ($value === null || '' === trim($value)) {
            return null;
        }

        ['sign' => $sign, 'wholePart' => $wholePart, 'decimalPart' => $decimalPart] = $this->parseNumber($value);

        return $this->scale > 0 ?
            ("{$sign}{$wholePart}.".str_pad($decimalPart, $this->scale, '0')) :
            "{$sign}{$wholePart}";
    }

    /**
     * @return array{sign: string, wholePart: string, decimalPart: string}
     */
    protected function parseNumber(string $value): array
    {
        $regex = '#^(?P<sign>-)?(?P<int>\d+)(?:\.(?P<dec>\d+))?$#';

        if (!preg_match($regex, $value, $matches)) {
            throw new TransformationFailedException('Invalid decimal amount', 0, null, $this->messageInvalid);
        }

        $sign = $matches['sign'];
        $wholePart = $matches['int'];
        $decimalPart = $matches['dec'] ?? '';

        $maxWholeDigits = ($this->precision - $this->scale);
        $translationParameters = [
            'maxWholeDigits' => $maxWholeDigits,
            'maxDecimalDigits' => $this->scale,
        ];

        if (strlen($wholePart) > $maxWholeDigits) {
            throw new TransformationFailedException('Too many digits (whole part)', 0, null, $this->messageTooLargeWholePart, $translationParameters);
        }

        if (strlen($decimalPart) > $this->scale) {
            throw new TransformationFailedException('Too many digits (decimal part)', 0, null, $this->messageTooLargeDecimals, $translationParameters);
        }

        return ['sign' => $sign, 'wholePart' => $wholePart, 'decimalPart' => $decimalPart];
    }
}
