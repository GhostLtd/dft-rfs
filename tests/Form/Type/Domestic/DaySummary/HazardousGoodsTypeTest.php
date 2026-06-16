<?php

namespace App\Tests\Form\Type\Domestic\DaySummary;

use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DaySummary\HazardousGoodsType;
use App\Tests\Form\Type\AbstractHazardousGoodsTypeTestCase;

class HazardousGoodsTypeTest extends AbstractHazardousGoodsTypeTestCase
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DaySummary::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return HazardousGoodsType::class;
    }
}
