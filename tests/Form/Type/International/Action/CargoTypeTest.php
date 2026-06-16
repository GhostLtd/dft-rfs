<?php

namespace App\Tests\Form\Type\International\Action;

use App\Entity\International\Action;
use App\Form\InternationalSurvey\Action\CargoTypeType;
use App\Tests\Form\Type\AbstractCargoTypeTypeTestCase;

class CargoTypeTest extends AbstractCargoTypeTypeTestCase
{
    #[\Override]
    protected function getDataClass(): string
    {
        return Action::class;
    }

    #[\Override]
    protected function getFormClass(): string
    {
        return CargoTypeType::class;
    }
}
