<?php

namespace App\Form\DomesticSurvey;

use App\Entity\Distance;
use Symfony\Component\Form\AbstractType;

class AbstractDistanceTravelledType extends AbstractType
{
    public const VALUE_OPTIONS = [
        'label' => 'Distance',
        'attr' => ['class' => 'govuk-input--width-5'],
        'precision' => 10,
        'scale' => 1,
    ];
    public const UNIT_OPTIONS = [
        'label' => 'Unit',
        'choices' => Distance::UNIT_CHOICES,
    ];
}
