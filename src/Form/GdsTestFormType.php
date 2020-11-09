<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type as GdsTypes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class GdsTestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [
            'Apple' => 'apple',
            'Banana' => 'banana',
            'Cabbage' => 'cabbage',
        ];

        $builder
            ->add('text', GdsTypes\InputType::class, [
                'constraints' => [new NotBlank()],
                'help' => "Here's some help text for text!",
            ])
            ->add('textarea', GdsTypes\TextareaType::class, [
                'constraints' => [new NotBlank()],
                'help' => "Here's some help text for textarea!",
            ])
            ->add('select', GdsTypes\ChoiceType::class, [
                'choices' => $choices,
                'placeholder' => '-- Please select --',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('radios', GdsTypes\ChoiceType::class, [
                'choices' => $choices,
                'expanded' => true,
                'help' => 'This includes changing your last name or spelling your name differently.',
                'constraints' => [
                    new NotBlank(),
                ],
//                'label_is_page_heading' => true,
//                'label_attr' => [
//                    'class' => 'govuk-fieldset__legend--xl',
//                ],
            ])
            ->add('checkboxes', GdsTypes\ChoiceType::class, [
                'choices' => $choices,
                'expanded' => true,
                'multiple' => true,
                'help' => 'This includes changing your last name or spelling your name differently.',
                'constraints' => [
                    new NotBlank(),
                ],
//                'label_is_page_heading' => true,
//                'label_attr' => [
//                    'class' => 'govuk-fieldset__legend--xl',
//                ],
            ])
            ->add('date', GdsTypes\DateType::class, [
                'label' => 'When was your passport issued?',
//                'label_is_page_heading' => true,
//                'label_attr' => [
//                    'class' => 'govuk-fieldset__legend--xl',
//                ],
                'help' => 'For example, 12 11 2007',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('money', GdsTypes\MoneyType::class)

            ->add('submit_button', GdsTypes\ButtonType::class, [
                'label' => 'Submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer('attr', function (Options $options, $value) {
            return array_merge(
                $value ?? [],
                ['novalidate' => 'novalidate']
            );
        });
    }
}
