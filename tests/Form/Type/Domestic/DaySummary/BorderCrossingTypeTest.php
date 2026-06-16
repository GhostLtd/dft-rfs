<?php

namespace App\Tests\Form\Type\Domestic\DaySummary;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DaySummary\BorderCrossingType;
use App\Tests\Form\Type\Domestic\AbstractBorderCrossingTypeTestCase;

class BorderCrossingTypeTest extends AbstractBorderCrossingTypeTestCase
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DaySummary::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return BorderCrossingType::class;
    }
}
