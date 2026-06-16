<?php

namespace App\Tests\Form\Type\Domestic\DayStop;

use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayStop\HazardousGoodsType;
use App\Tests\Form\Type\AbstractHazardousGoodsTypeTestCase;

class HazardousGoodsTypeTest extends AbstractHazardousGoodsTypeTestCase
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
