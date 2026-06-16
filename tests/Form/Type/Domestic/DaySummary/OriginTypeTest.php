<?php

namespace App\Tests\Form\Type\Domestic\DaySummary;

use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DaySummary\OriginType;
use App\Tests\Form\Type\AbstractOriginTypeTestCase;

class OriginTypeTest extends AbstractOriginTypeTestCase
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DaySummary::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return OriginType::class;
    }
}
