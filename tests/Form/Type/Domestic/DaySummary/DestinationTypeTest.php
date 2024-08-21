<?php

namespace App\Tests\Form\Type\Domestic\DaySummary;

use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DaySummary\DestinationType;
use App\Tests\Form\Type\AbstractDestinationTypeTest;

class DestinationTypeTest extends AbstractDestinationTypeTest
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DaySummary::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return DestinationType::class;
    }
}
