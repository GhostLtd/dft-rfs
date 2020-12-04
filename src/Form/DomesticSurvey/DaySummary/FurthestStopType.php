<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class FurthestStopType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.day-summary.furthest-stop";
        $builder
            ->add('furthestStop', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.furthest-stop.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-label--xl'],
                'help' => "{$translationKeyPrefix}.furthest-stop.help",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
            'validation_groups' => 'day-summary.furthest-stop'
        ]);
    }
}
