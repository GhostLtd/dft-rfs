<?php

namespace App\Form\InternationalSurvey\Trip;

use App\Entity\International\Trip;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractCargoStateType extends AbstractType
{
    const CHOICE_YES = 'common.choices.boolean.yes';
    const CHOICE_NO = 'common.choices.boolean.no';
    const CHOICES = [
        self::CHOICE_YES,
        self::CHOICE_NO,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        $direction = $options['direction'];
//        $prefix = "international.trip.{$direction}-cargo-state";
        $emptyPrefix = "{prefix}.was-empty";

        $builder
            ->add("wasEmpty", Gds\ChoiceType::class, [
                'label' => "{$emptyPrefix}.label",
                'help' => "{$emptyPrefix}.help",
                'attr' => ['class' => 'govuk-input--width-10'],
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => self::CHOICES,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'direction',
        ]);

        $resolver->setAllowedValues('direction', ['outbound', 'return']);
        $resolver->setDefaults([
            'data_class' => Trip::class,
        ]);
    }
}
