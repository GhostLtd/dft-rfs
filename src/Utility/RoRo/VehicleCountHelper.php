<?php

namespace App\Utility\RoRo;

use App\Entity\RoRo\VehicleCount;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Intl\Countries;
use Symfony\Contracts\Translation\TranslatorInterface;

class VehicleCountHelper
{
    /** @var Collection<VehicleCount> */
    protected Collection $vehicleCounts;

    public function __construct(protected TranslatorInterface $translator)
    {}

    /** @param Collection<VehicleCount> $vehicleCounts */
    public function setVehicleCountLabels(Collection $vehicleCounts): self
    {
        $this->vehicleCounts = $vehicleCounts;
        foreach($vehicleCounts as $vehicleCount) {
            $countryCode = $vehicleCount->getCountryCode() ?? '';

            if (Countries::exists($countryCode)) {
                $label = Countries::getName($countryCode);
            } else {
                $code = $countryCode ?: $vehicleCount->getOtherCode();
                $label = $this->translator->trans("roro.survey.vehicle-count.others.{$code}");
            }

            $vehicleCount->setLabel($label);
        }

        return $this;
    }
}