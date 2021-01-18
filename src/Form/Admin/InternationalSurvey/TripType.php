<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\Distance;
use App\Form\Admin\InternationalSurvey\DataMapper\TripDataMapper;
use App\Form\InternationalSurvey\Trip\CountriesTransittedType;
use App\Form\ValueUnitType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TripType extends AbstractType
{
    const CARGO_STATE_EMPTY = 'empty';
    const CARGO_STATE_PART_FILLED = 'part-filled';
    const CARGO_STATE_CAPACITY_SPACE = 'space';
    const CARGO_STATE_CAPACITY_WEIGHT = 'weight';
    const CARGO_STATE_CAPACITY_BOTH = 'weight-and-space';

    const CARGO_STATE_CHOICES = [
        'Empty' => self::CARGO_STATE_EMPTY,
        'Partially filled' => self::CARGO_STATE_PART_FILLED,
        'At capacity by space' => self::CARGO_STATE_CAPACITY_SPACE,
        'At capacity by weight' => self::CARGO_STATE_CAPACITY_WEIGHT,
        'At capacity by space and weight' => self::CARGO_STATE_CAPACITY_BOTH,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addDirectionalFields($builder, 'outbound');
        $this->addDirectionalFields($builder, 'return');

        $this
            ->addFieldSet($builder, 'otherDetails', 'Other Details')
            ->add('roundTripDistance', ValueUnitType::class, [
                'label' => "Round trip distance",
                'label_attr' => ['class' => 'govuk-label--s'],
                'value_options' => [
                    'label' => 'Distance',
                    'is_decimal' => true,
                    'attr' => ['class' => 'govuk-input--width-5'],
                ],
                'unit_options' => [
                    'label' => 'common.value-unit.unit',
                    'choices' => Distance::UNIT_CHOICES,
                ],
                'data_class' => Distance::class,
            ]);

        $builder
            ->setDataMapper(new TripDataMapper())
            ->add('countries', CountriesTransittedType::class, [
                'inherit_data' => true,
                'label' => 'Which countries were travelled through?',
                'label_attr' => ['class' => 'govuk-label--m govuk-label--underline'],
                'attr' => ['class' => 'govuk-!-margin-bottom-9'],
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Save changes',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    protected function addDirectionalFields(FormBuilderInterface $builder, string $direction)
    {
        $builder
            ->add("${direction}Date", Gds\DateType::class, [
                'label' => "Date",
                'label_attr' => ['class' => 'govuk-label--s'],
            ]);

        foreach (['UkPort' => 'UK Port', 'ForeignPort' => 'Foreign Port'] as $portField => $label) {
            $builder->add("${direction}${portField}", Gds\InputType::class, [
                'label' => $label,
                'attr' => ['class' => 'govuk-input--width-10'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ]);
        }

        $builder->add("{$direction}CargoState", Gds\ChoiceType::class, [
            'choices' => self::CARGO_STATE_CHOICES,
            'expanded' => false,
            'label' => 'Cargo state',
            'label_attr' => ['class' => 'govuk-label--s'],
        ]);
    }

    protected function addFieldSet(FormBuilderInterface $builder, string $fieldName, string $label): FormBuilderInterface
    {
        $builder->add($fieldName, Gds\FieldsetType::class, [
            'label' => $label,
            'label_attr' => ['class' => 'govuk-label--m govuk-fieldset__legend--underline'],
            'attr' => ['class' => 'govuk-!-margin-bottom-9'],
        ]);

        return $builder->get($fieldName);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['admin_trip'],
        ]);
    }
}