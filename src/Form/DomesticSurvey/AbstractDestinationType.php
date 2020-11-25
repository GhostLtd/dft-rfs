<?php

namespace App\Form\DomesticSurvey;

use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\StopTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractDestinationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.{$options['translation_entity_key']}.destination";
        $builder
            ->add('destinationLocation', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.destination-location.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "${translationKeyPrefix}.destination-location.help",
            ])
            ->add('goodsUnloaded', Gds\ChoiceType::class, [
                'choices' => ['Yes' => true, 'No' => false],
                'label' => "${translationKeyPrefix}.goods-unloaded.label",
                'help' => "${translationKeyPrefix}.goods-unloaded.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;
    }

    use StopTypeTrait;
}
