<?php

namespace App\Form\InternationalSurvey\Trip;

use App\Entity\International\Trip;
use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Exception;

class ChangedBodyTypeType extends AbstractType implements DataMapperInterface
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
            ->add('isChangedBodyType', Gds\ChoiceType::class, [
                'choices' => self::CHOICES,
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'label' => "international.trip.changed-body-type.is-changed-body-type.label",
                'help' => "international.trip.changed-body-type.is-changed-body-type.help",
                'choice_options' => [
                    self::CHOICE_YES => [
                        'conditional_form_name' => 'bodyType',
                    ],
                ],
                'choice_value' => fn($x) => match($x) {
                    true => 'yes',
                    false => 'no',
                    null => null,
                },
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Trip $trip */
            $trip = $event->getData();
            $event->getForm()
                ->add('bodyType', Gds\ChoiceType::class, [
                    'choices' => $trip->getChangeBodyTypeChoices(),
                    'label' => "international.trip.changed-body-type.body-type.label",
                    'help' => "international.trip.changed-body-type.body-type.help",
                    'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                ]);
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
            'validation_groups' => 'trip_changed_body_type'
        ]);
    }

    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof Trip) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        if (!isset($forms['isChangedBodyType']) || !isset($forms['bodyType'])) {
            return;
        }

        $forms['isChangedBodyType']->setData($viewData->getIsChangedBodyType());
        $forms['bodyType']->setData($viewData->getBodyType());
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);

        /** @var FormInterface[] $forms */
        if (!$viewData instanceof Trip) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
        }

        $isChanged = $forms['isChangedBodyType']->getData();
        $viewData->setIsChangedBodyType($isChanged);
        $viewData->setBodyType(($isChanged === true)
            ? $forms['bodyType']->getData()
            : null
        );
    }
}
