<?php

namespace App\Tests\Form\Type\Domestic\DayStop;

use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayStop\HazardousGoodsType;
use App\Tests\Form\Type\AbstractHazardousGoodsTypeTest;

class HazardousGoodsTypeTest extends AbstractHazardousGoodsTypeTest
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DayStop::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return HazardousGoodsType::class;
    }
}
