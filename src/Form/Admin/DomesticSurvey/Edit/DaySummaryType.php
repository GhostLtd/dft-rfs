<?php


namespace App\Form\Admin\DomesticSurvey\Edit;


use App\Entity\Domestic\DaySummary;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DaySummaryType extends AbstractStopType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
        ]);
    }
}