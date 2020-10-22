<?php

namespace Ghost\GovUkFrontendBundle\Form\DataTransformer;

use Ghost\GovUkFrontendBundle\Model\Time;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TimeStringTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        if (!$value) {
            return [
                'hour' => null,
                'minute' => null,
                'am_or_pm' => null,
            ];
        }

        if (!$value instanceof Time) {
            throw new TransformationFailedException('Invalid value');
        }

        return [
            'hour' => $value->format('g'),
            'minute' => $value->format('i'),
            'am_or_pm' => $value->format('a'),
        ];
    }

    public function reverseTransform($value)
    {
        if (!is_array($value)) {
            throw new TransformationFailedException('Invalid value');
        }

        $hour = $value['hour'] ?? null;
        $minute = $value['minute'] ?? null;
        $amOrPm = $value['am_or_pm'] ?? null;

        if (!$hour && !$minute && !$amOrPm) {
            return null;
        }

        if (null === $hour) {
            throw new TransformationFailedException('Invalid hour value', 0, null, 'Enter an hour (1-12)');
        }

        if (null === $minute) {
            throw new TransformationFailedException('Invalid minute value', 0, null, 'Enter a minutes value (0-59)');
        }

        if (null === $amOrPm) {
            throw new TransformationFailedException('Invalid am/pm value', 0, null, 'Select "am" or "pm"');
        }

        $hour = intval($hour);
        $minute = intval($minute);

        if ($hour < 1 || $hour > 12) {
            throw new TransformationFailedException('Invalid hour value', 0, null, 'Enter a real hour value (1-12)');
        }

        if ($minute < 0 || $minute > 59) {
            throw new TransformationFailedException('Invalid hour value', 0, null, 'Enter a real minutes value (0-59)');
        }

        return new Time(sprintf('%d:%02d%s', $hour, $minute, $amOrPm));
    }
}
