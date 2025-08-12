<?php

namespace App\Utility\Domestic\EmptyDayDetection;

use App\Entity\Domestic\Day;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('csrgt.empty_day.detector')]
interface DetectorInterface
{
    public function detect(Day $day): bool;
}