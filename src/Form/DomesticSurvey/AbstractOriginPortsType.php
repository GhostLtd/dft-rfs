<?php

namespace App\Form\DomesticSurvey;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\StopTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractOriginPortsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.{$options['translation_entity_key']}.origin-ports";
        $builder
            ->add('goodsTransferredFrom', Gds\ChoiceType::class, [
                'choices' => Day::TRANSFER_CHOICES,
                'label' => "{$translationKeyPrefix}.goods-transferred-from.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'help' => "{$translationKeyPrefix}.goods-transferred-from.help",
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
            'validation_groups' => 'origin-ports'
        ]);
    }

}
