<?php

namespace App\Tests\Form\Type\Domestic\DayStop;

use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayStop\DestinationType;
use App\Tests\Form\Type\AbstractDestinationTypeTestCase;

class DestinationTypeTest extends AbstractDestinationTypeTestCase
{
    #[\Override]
    protected function getDataClass(): string
    {
        return DayStop::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return DestinationType::class;
    }
}
