<?php

namespace App\Form\InternationalSurvey\Trip;

use App\Entity\International\Trip;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SwappedTrailerType extends AbstractType implements DataMapperInterface
{
    public const CHOICE_YES = 'common.choices.boolean.yes';
    public const CHOICE_NO = 'common.choices.boolean.no';
    public const CHOICES = [
        self::CHOICE_YES => true,
        self::CHOICE_NO => false,
    ];

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this)
            ->add('isSwappedTrailer', Gds\ChoiceType::class, [
                'choices' => self::CHOICES,
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'label' => "international.trip.swapped-trailer.is-swapped-trailer.label",
                'help' => "international.trip.swapped-trailer.is-swapped-trailer.help",
                'choice_options' => [
                    self::CHOICE_YES => [
                        'conditional_form_name' => 'axleConfiguration',
                    ],
                ],
                'choice_value' => fn($x) => match($x) {
                    true => 'yes',
                    false => 'no',
                    null => null,
                },
            ])
            ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Trip $trip */
            $trip = $event->getData();
            $event->getForm()
                ->add('axleConfiguration', Gds\ChoiceType::class, [
                    'choices' => $trip->getTrailerSwapChoices(),
                    'label' => "international.trip.swapped-trailer.axle-configuration.label",
                    'help' => "international.trip.swapped-trailer.axle-configuration.help",
                    'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                ])
            ;
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
            'validation_groups' => 'trip_swapped_trailer'
        ]);
    }

    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof Trip) {
            throw new UnexpectedTypeException($viewData, Trip::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        if (!isset($forms['isSwappedTrailer']) || !isset($forms['axleConfiguration'])) {
            return;
        }

        $forms['isSwappedTrailer']->setData($viewData->getIsSwappedTrailer());
        $forms['axleConfiguration']->setData($viewData->getAxleConfiguration());
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);

        /** @var FormInterface[] $forms */
        if (!$viewData instanceof Trip) {
            throw new UnexpectedTypeException($viewData, Trip::class);
        }

        $isSwapped = $forms['isSwappedTrailer']->getData();
        $viewData->setIsSwappedTrailer($isSwapped);
        $viewData->setAxleConfiguration(($isSwapped === true)
            ? $forms['axleConfiguration']->getData()
            : null
        );
    }
}
