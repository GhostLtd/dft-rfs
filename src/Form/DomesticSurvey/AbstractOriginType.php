<?php

namespace App\Form\DomesticSurvey;

use App\Entity\Domestic\StopTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractOriginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.{$options['translation_entity_key']}.origin";
        $builder
            ->add('originLocation', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.origin-location.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.origin-location.help",
            ])
            ->add('goodsLoaded', Gds\ChoiceType::class, [
                'choices' => ['Yes' => true, 'No' => false],
                'label' => "{$translationKeyPrefix}.goods-loaded.label",
                'help' => "{$translationKeyPrefix}.goods-loaded.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;
    }

    use StopTypeTrait;
}