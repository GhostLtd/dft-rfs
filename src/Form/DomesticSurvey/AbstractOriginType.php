<?php

namespace App\Form\DomesticSurvey;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\StopInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractOriginType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->traitConfigureOptions($resolver);

        $resolver->setDefaults([
            'validation_groups' => function(FormInterface $form) {
                /** @var StopInterface $data */
                $data = $form->getData();
                $groups = [];

                if ($data instanceof DayStop) {
                    $groups[] = 'origin-day-stop';
                } else if ($data instanceof DaySummary) {
                    $groups[] = 'origin-day';
                }

                if ($data->getGoodsLoaded()) {
                    $groups[] = 'origin-ports';
                }

                return $groups;
            },
        ]);
    }
}
