<?php

namespace App\Tests\Form\Type\Domestic\DayStop;

use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayStop\BorderCrossingType;
use App\Tests\Form\Type\Domestic\AbstractBorderCrossingTypeTestCase;

class BorderCrossingTypeTest extends AbstractBorderCrossingTypeTestCase
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DayStop::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return BorderCrossingType::class;
    }
}
