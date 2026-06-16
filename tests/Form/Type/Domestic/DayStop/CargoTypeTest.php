<?php

namespace App\Tests\Form\Type\Domestic\DayStop;

use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayStop\CargoTypeType;
use App\Tests\Form\Type\AbstractCargoTypeTypeTestCase;

class CargoTypeTest extends AbstractCargoTypeTypeTestCase
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
