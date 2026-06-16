<?php

namespace App\Tests\Form\Type\Domestic\DayStop;

use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayStop\OriginType;
use App\Tests\Form\Type\AbstractOriginTypeTestCase;

class OriginTypeTest extends AbstractOriginTypeTestCase
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DayStop::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return OriginType::class;
    }
}
