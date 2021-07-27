<?php


namespace App\Form\InternationalSurvey\Trip;


use App\Entity\International\Trip;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Exception;

class SwappedTrailerType extends AbstractType implements DataMapperInterface
{
    const CHOICE_YES = 'common.choices.boolean.yes';
    const CHOICE_NO = 'common.choices.boolean.no';
    const CHOICES = [
        self::CHOICE_YES => true,
        self::CHOICE_NO => false,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "international.trip.swapped-trailer";
        $builder
            ->setDataMapper($this)
            ->add('isSwappedTrailer', Gds\ChoiceType::class, [
                'choices' => self::CHOICES,
                'label_attr' => ['class' => 'govuk-label--s'],
                'label' => "${translationKeyPrefix}.is-swapped-trailer.label",
                'help' => "${translationKeyPrefix}.is-swapped-trailer.help",
                'choice_options' => [
                    self::CHOICE_YES => [
                        'conditional_form_name' => 'axleConfiguration',
                    ],
                ],
            ])
            ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($translationKeyPrefix) {
            /** @var Trip $trip */
            $trip = $event->getData();
            $event->getForm()
                ->add('axleConfiguration', Gds\ChoiceType::class, [
                    'choices' => $trip->getTrailerSwapChoices(),
                    'label' => "{$translationKeyPrefix}.axle-configuration.label",
                    'help' => "{$translationKeyPrefix}.axle-configuration.help",
                    'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                ])
            ;
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
            'validation_groups' => 'trip_swapped_trailer'
        ]);
    }

    public function mapDataToForms($viewData, $forms)
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof Trip) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        if (!isset($forms['isSwappedTrailer']) || !isset($forms['axleConfiguration'])) {
            return;
        }

        $forms['isSwappedTrailer']->setData($viewData->getIsSwappedTrailer());
        $forms['axleConfiguration']->setData($viewData->getAxleConfiguration());
    }

    public function mapFormsToData($forms, &$viewData)
    {
        $forms = iterator_to_array($forms);

        /** @var FormInterface[] $forms */
        if (!$viewData instanceof Trip) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
        }

        $isSwapped = $forms['isSwappedTrailer']->getData();
        $viewData->setIsSwappedTrailer($isSwapped);
        $viewData->setAxleConfiguration(($isSwapped === true)
            ? $forms['axleConfiguration']->getData()
            : null
        );
    }
}