<?php

namespace App\Tests\Form\Type\Domestic\DayStop;

use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayStop\GoodsDescriptionType;
use App\Tests\Form\Type\AbstractGoodsDescriptionTypeTest;

class GoodsDescriptionTypeTest extends AbstractGoodsDescriptionTypeTest
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DayStop::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return GoodsDescriptionType::class;
    }

    #[\Override]
    protected function shouldIncludeEmpty(): bool
    {
        return true;
    }
}
