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
    const CHOICE_YES = 'common.choices.boolean.yes';
    const CHOICE_NO = 'common.choices.boolean.no';
    const CHOICES = [
        self::CHOICE_YES => true,
        self::CHOICE_NO => false,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "international.trip.changed-body-type";
        $builder
            ->setDataMapper($this)
            ->add('isChangedBodyType', Gds\ChoiceType::class, [
                'choices' => self::CHOICES,
                'label_attr' => ['class' => 'govuk-label--s'],
                'label' => "${translationKeyPrefix}.is-changed-body-type.label",
                'help' => "${translationKeyPrefix}.is-changed-body-type.help",
                'choice_options' => [
                    self::CHOICE_YES => [
                        'conditional_form_name' => 'bodyType',
                    ],
                ],
            ])
            ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($translationKeyPrefix) {
            /** @var Trip $trip */
            $trip = $event->getData();
            $event->getForm()
                ->add('bodyType', Gds\ChoiceType::class, [
                    'choices' => $trip->getChangeBodyTypeChoices(),
                    'label' => "{$translationKeyPrefix}.body-type.label",
                    'help' => "{$translationKeyPrefix}.body-type.help",
                    'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                ])
            ;
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
            'validation_groups' => 'trip_changed_body_type'
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
        if (!isset($forms['isChangedBodyType']) || !isset($forms['bodyType'])) {
            return;
        }

        $forms['isChangedBodyType']->setData($viewData->getIsChangedBodyType());
        $forms['bodyType']->setData($viewData->getBodyType());
    }

    public function mapFormsToData($forms, &$viewData)
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