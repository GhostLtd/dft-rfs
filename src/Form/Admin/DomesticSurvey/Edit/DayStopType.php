<?php

namespace App\Form\Admin\DomesticSurvey\Edit;

use App\Entity\Domestic\DayStop;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DayStopType extends AbstractStopType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => DayStop::class,
        ]);
    }
}
