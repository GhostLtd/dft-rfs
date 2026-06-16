<?php

namespace App\Form\DomesticSurvey;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\StopInterface;
use App\Form\Validator\Postcode;
use App\Utility\PostcodeChangeoverHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractOriginType extends AbstractType
{
    public function __construct(private PostcodeChangeoverHelper $changeoverHelper)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "domestic.{$options['translation_entity_key']}.origin";
        $portsTranslationKeyPrefix = "domestic.{$options['translation_entity_key']}.origin-ports";

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($translationKeyPrefix): void {
            $form = $event->getForm();
            /** @var StopInterface|null $data */
            $data = $event->getData();

            if (!$data) {
                return;
            }

            $survey = $data->getDay()?->getResponse()?->getSurvey();
            $isPostcodeOnly = $this->changeoverHelper->isPostcodeOnly($survey);

            if ($isPostcodeOnly) {
                $form->add('originLocation', Gds\InputType::class, [
                    'label' => "{$translationKeyPrefix}.origin-location.postcode-only.label",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'help' => "{$translationKeyPrefix}.origin-location.postcode-only.help",
                    'constraints' => [new Postcode(allowPartial: true, groups: ['origin-day', 'origin-day-stop'])],
                ]);
            } else {
                $form->add('originLocation', Gds\InputType::class, [
                    'label' => "{$translationKeyPrefix}.origin-location.label",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'help' => "{$translationKeyPrefix}.origin-location.help",
                ]);
            }
        });

        $builder
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
