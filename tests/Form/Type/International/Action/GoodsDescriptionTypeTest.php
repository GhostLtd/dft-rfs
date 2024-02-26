<?php

namespace App\Tests\Form\Type\International\Action;

use App\Entity\International\Action;
use App\Form\InternationalSurvey\Action\GoodsDescriptionType;
use App\Tests\Form\Type\AbstractGoodsDescriptionTypeTest;

class GoodsDescriptionTypeTest extends AbstractGoodsDescriptionTypeTest
{
    #[\Override]
    protected function getDataClass(): string
    {
        return Action::class;
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
