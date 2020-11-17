<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class OriginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('originLocation', Gds\InputType::class, [
                'label' => 'survey.domestic.forms.day-summary.origin-location.label',
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => 'survey.domestic.forms.day-summary.origin-location.help',
            ])
            ->add('goodsLoaded', Gds\ChoiceType::class, [
                'choices' => ['Yes' => true, 'No' => false],
                'label' => 'survey.domestic.forms.day-summary.goods-loaded.label',
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
