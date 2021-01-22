<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

abstract class AbstractConfirmType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('confirm', ChoiceType::class, array_merge($options['field_options'], [
                'mapped' => false,
                'label' => "{$options['translation_key_prefix']}.confirm.label",
                'help' => "{$options['translation_key_prefix']}.confirm.help",
                'choices' => [
                    'common.choices.boolean.yes' => true,
                    'common.choices.boolean.no' => false,
                ],
                'constraints' => new NotNull(['message' => 'common.choice.not-null']),
            ]))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
            'field_options' => [
                'label_attr' => ['class' => 'govuk-label--xl'],
            ],
        ]);
        $resolver->setRequired([
            'translation_key_prefix',
        ]);
    }
}
