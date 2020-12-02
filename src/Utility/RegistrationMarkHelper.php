<?php


namespace App\Utility;


class RegistrationMarkHelper
{
    protected $registrationMark;

    protected $formattedRegistrationMark;

    protected $valid;

    protected $current;

    public function __construct(?string $registrationMark)
    {
        // Based upon https://gist.github.com/danielrbradley/7567269
        $currentRegex = "/^[A-HJ-PR-Y]{2}[0-9]{2}[A-Z]{3}$/";
        $regex = "/(?<Current>^[A-HJ-PR-Y]{2}[0-9]{2}[A-Z]{3}$)|(?<Prefix>^[A-Z][0-9]{1,3}[A-Z]{3}$)|(?<Suffix>^[A-Z]{3}[0-9]{1,3}[A-Z]$)|(?<DatelessLongNumberPrefix>^[0-9]{1,4}[A-Z]{1,2}$)|(?<DatelessShortNumberPrefix>^[0-9]{1,3}[A-Z]{1,3}$)|(?<DatelessLongNumberSuffix>^[A-Z]{1,2}[0-9]{1,4}$)|(?<DatelessShortNumberSufix>^[A-Z]{1,3}[0-9]{1,3}$)|(?<DatelessNorthernIreland>^[A-Z]{1,3}[0-9]{1,4}$)|(?<DiplomaticPlate>^[0-9]{3}[DX]{1}[0-9]{3}$)/";

        if ($registrationMark === null) {
            $this->valid = $this->current = false;
            $this->formattedRegistrationMark = '';
        } else {
            $this->registrationMark = strtoupper(str_replace(' ', '', $registrationMark));

            $this->current = !!preg_match($currentRegex, $this->registrationMark);
            $this->valid = $this->current || !!preg_match($regex, $this->registrationMark);

            if ($this->valid) {
                // We only format (with a space, that is) the output of modern (current) registration marks, as this
                // will cover almost all cases (i.e. haulage with a 20+ year old truck is exceptionally unlikely!)
                $this->formattedRegistrationMark = $this->current ?
                    substr($this->registrationMark, 0, 4).' '.substr($this->registrationMark, 4) :
                    $this->registrationMark;
            }
        }
    }

    public function getRegistrationMark(): ?string
    {
        return $this->registrationMark;
    }

    public function getFormattedRegistrationMark(): ?string
    {
        return $this->formattedRegistrationMark;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function isCurrent(): bool
    {
        return $this->current;
    }
}