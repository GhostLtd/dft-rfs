<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\International\Action;
use App\Form\CountryType;
use App\Utility\LoadingPlaceHelper;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ActionUnloadType extends AbstractType implements DataMapperInterface
{
    const CHOICE_YES = 'common.choices.boolean.yes';
    const CHOICE_NO = 'common.choices.boolean.no';
    const CHOICES = [
        self::CHOICE_YES => true,
        self::CHOICE_NO => false,
    ];

    protected $loadingPlaceHelper;

    public function __construct(LoadingPlaceHelper $loadingPlaceHelper)
    {
        $this->loadingPlaceHelper = $loadingPlaceHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setDataMapper($this)
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
                /** @var Action $action */
                $action = $event->getData();

                [$choices, $choiceOptions] = $this->loadingPlaceHelper->getChoicesAndOptionsForPlace($action, true);

                $event->getForm()
                    ->add('name', Gds\InputType::class, [
                        'label' => "Place",
                        'attr' => ['class' => 'govuk-input--width-10'],
                        'label_attr' => ['class' => 'govuk-label--s'],
                    ])
                    ->add('country', CountryType::class)
                    ->add('loadingAction', Gds\ChoiceType::class, [
                        'label' => "Which goods were unloaded?",
                        'label_attr' => ['class' => 'govuk-label--s'],
                        'choices' => $choices,
                        'choice_options' => $choiceOptions,
                    ])
                    ->add("weightUnloadedAll", Gds\ChoiceType::class, [
                        'label' => "Were all of the goods unloaded?",
                        'label_attr' => ['class' => 'govuk-label--s'],
                        'choices' => self::CHOICES,
                        'choice_options' => [
                            self::CHOICE_NO => [
                                'conditional_form_name' => 'weightOfGoods',
                            ],
                        ],
                    ])
                    ->add('weightOfGoods', Gds\NumberType::class, [
                        'label' => "Weight of goods unloaded",
                        'label_attr' => ['class' => 'govuk-label--s'],
                        'attr' => ['class' => 'govuk-input--width-10'],
                        'suffix' => 'kg',
                    ])
                    ->add('submit', Gds\ButtonType::class, [
                        'label' => 'Save changes',
                    ])
                    ->add('cancel', Gds\ButtonType::class, [
                        'label' => 'Cancel',
                        'attr' => ['class' => 'govuk-button--secondary'],
                    ]);
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['admin_action_unload'],
        ]);
    }

    public function mapDataToForms($viewData, $forms)
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof Action) {
            throw new UnexpectedTypeException($viewData, Action::class);
        }

        $forms = iterator_to_array($forms);

        /** @var FormInterface[] $forms */
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach(['name', 'country', 'country_other', 'loadingAction', 'weightOfGoods', 'weightUnloadedAll'] as $field) {
            $forms[$field]->setData($accessor->getValue($viewData, $field));
        }
    }

    public function mapFormsToData($forms, &$viewData)
    {
        $forms = iterator_to_array($forms);

        /** @var FormInterface[] $forms */
        if (!$viewData instanceof Action) {
            throw new UnexpectedTypeException($viewData, Action::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach(['name', 'country', 'country_other', 'loadingAction', 'weightOfGoods', 'weightUnloadedAll'] as $field) {
            $accessor->setValue($viewData, $field, $forms[$field]->getData());
        }

        $weightUnloadedAll = $forms['weightUnloadedAll']->getData();
        if ($weightUnloadedAll) {
            $accessor->setValue($viewData, 'weightOfGoods', null);
        }
    }
}