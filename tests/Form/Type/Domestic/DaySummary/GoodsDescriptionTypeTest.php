<?php

namespace App\Tests\Form\Type\Domestic\DaySummary;

use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DaySummary\GoodsDescriptionType;
use App\Tests\Form\Type\AbstractGoodsDescriptionTypeTest;

class GoodsDescriptionTypeTest extends AbstractGoodsDescriptionTypeTest
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DaySummary::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return GoodsDescriptionType::class;
    }

    #[\Override]
    protected function shouldIncludeEmpty(): bool
    {
        return false;
    }
}
