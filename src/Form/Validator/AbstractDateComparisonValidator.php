<?php

namespace App\Form\Validator;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;
use Locale;
use Symfony\Component\Validator\Constraints\AbstractComparisonValidator;

abstract class AbstractDateComparisonValidator extends AbstractComparisonValidator
{
    #[\Override]
    protected function formatValue($value, $format = 0): string
    {
        if ($value instanceof DateTimeInterface) {
            if (class_exists('IntlDateFormatter')) {
                $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, 'UTC');

                return $formatter->format(new DateTime(
                    $value->format('Y-m-d H:i:s.u'),
                    new DateTimeZone('UTC')
                ));
            }

            return $value->format('Y-m-d');
        }

        return parent::formatValue($value, $format);
    }
}
