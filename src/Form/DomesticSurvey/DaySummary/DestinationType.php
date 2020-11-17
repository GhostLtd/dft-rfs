<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class DestinationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('destinationLocation', Gds\InputType::class, [
                'label' => 'survey.domestic.forms.day-summary.destination-location.label',
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => 'survey.domestic.forms.day-summary.destination-location.help',
            ])
            ->add('goodsUnloaded', Gds\ChoiceType::class, [
                'choices' => ['Yes' => true, 'No' => false],
                'label' => 'survey.domestic.forms.day-summary.goods-unloaded.label',
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
        ]);
    }
}
