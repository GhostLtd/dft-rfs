<?php

namespace App\Tests\Form\Type\Domestic\DaySummary;

use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DaySummary\CargoTypeType;
use App\Tests\Form\Type\AbstractCargoTypeTypeTestCase;

class CargoTypeTest extends AbstractCargoTypeTypeTestCase
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DaySummary::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return CargoTypeType::class;
    }
}
