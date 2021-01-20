<?php

namespace App\Form\DomesticSurvey;

use App\Entity\Domestic\Day;
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
        $portsTranslationKeyPrefix = "domestic.{$options['translation_entity_key']}.origin-ports";

        $builder
            ->add('originLocation', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.origin-location.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.origin-location.help",
            ])
            ->add('goodsLoaded', Gds\ChoiceType::class, [
                'choices' => ['Yes' => true, 'No' => false],
                'choice_options' => ['Yes' => [
                    'conditional_form_name' => 'goodsTransferredFrom',
                ]],
                'label' => "{$translationKeyPrefix}.goods-loaded.label",
                'help' => "{$translationKeyPrefix}.goods-loaded.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('goodsTransferredFrom', Gds\ChoiceType::class, [
                'choices' => Day::TRANSFER_CHOICES,
                'label' => "{$portsTranslationKeyPrefix}.goods-transferred-from.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "{$portsTranslationKeyPrefix}.goods-transferred-from.help",
            ])
        ;
    }

    use StopTypeTrait {
        configureOptions as traitConfigureOptions;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $this->traitConfigureOptions($resolver);

        $resolver->setDefaults([
            'validation_groups' => ['origin', 'origin-ports']
        ]);
    }
}
