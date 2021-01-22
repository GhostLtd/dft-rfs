<?php

namespace App\Form\DomesticSurvey\ClosingDetails;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Validator\Constraints\NotNull;

class MissingDaysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.closing-details.missing-days";
        $builder
            ->add('missing_days', Gds\ChoiceType::class, [
                'mapped' => false,
                'label' => "{$translationKeyPrefix}.missing-days.label",
                'help' => "{$translationKeyPrefix}.missing-days.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    "common.choices.boolean.yes" => true,
                    "common.choices.boolean.no" => false,
                ],
                'attr' => ['class' => 'govuk-radios--inline'],
                'constraints' => new NotNull(['message' => 'common.choice.not-null'])
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}
