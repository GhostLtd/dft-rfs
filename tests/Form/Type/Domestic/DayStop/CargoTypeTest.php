<?php

namespace App\Tests\Form\Type\Domestic\DayStop;

use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayStop\CargoTypeType;
use App\Tests\Form\Type\AbstractCargoTypeTypeTest;

class CargoTypeTest extends AbstractCargoTypeTypeTest
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DayStop::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return CargoTypeType::class;
    }
}
