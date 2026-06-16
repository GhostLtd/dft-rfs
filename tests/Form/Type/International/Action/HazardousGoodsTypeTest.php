<?php

namespace App\Tests\Form\Type\International\Action;

use App\Entity\International\Action;
use App\Form\InternationalSurvey\Action\HazardousGoodsType;
use App\Tests\Form\Type\AbstractHazardousGoodsTypeTestCase;

class HazardousGoodsTypeTest extends AbstractHazardousGoodsTypeTestCase
{
    #[\Override]
    protected function getDataClass(): string
    {
        return Action::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return HazardousGoodsType::class;
    }
}
