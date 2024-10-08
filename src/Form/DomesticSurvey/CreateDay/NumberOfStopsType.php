<?php

namespace App\Form\DomesticSurvey\CreateDay;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class NumberOfStopsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "domestic.day.number-of-stops";
        $builder
            ->add('hasMoreThanFiveStops', ChoiceType::class, [
                'choices' => [
                    "{$translationKeyPrefix}.number-of-stops.fewer-than-5.label" => false,
                    "{$translationKeyPrefix}.number-of-stops.5-or-more.label" => true,
                ],
                'choice_options' => [
                    "{$translationKeyPrefix}.number-of-stops.fewer-than-5.label" => [
                        'help' => "{$translationKeyPrefix}.number-of-stops.fewer-than-5.help",
                    ],
                    "{$translationKeyPrefix}.number-of-stops.5-or-more.label" => [
                        'help' => "{$translationKeyPrefix}.number-of-stops.5-or-more.help",
                    ],
                ],
                'label' => "{$translationKeyPrefix}.number-of-stops.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'label_is_page_heading' => true,
                'label_translation_parameters' => ['dayNumber' => $options['day_number']],
                'help_html' => true,
                'help' => "{$translationKeyPrefix}.number-of-stops.help",
                'constraints' => new NotNull(['message' => 'domestic.number-of-stops.choice'])
            ])
            ->add('continue', ButtonType::class, [
                'type' => 'submit',
            ])
            ->add('cancel', ButtonType::class, [
                'type' => 'submit',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('day_number');
    }
}
