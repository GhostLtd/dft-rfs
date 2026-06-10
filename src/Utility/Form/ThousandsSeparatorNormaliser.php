<?php

namespace App\Utility\Form;

class ThousandsSeparatorNormaliser
{
    public function normalise(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        if (preg_match('/^\d{1,3}(,\d{3})+$/', $value)) {
            return str_replace(',', '', $value);
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            return str_replace('.', '', $value);
        }

        return $value;
    }
}
