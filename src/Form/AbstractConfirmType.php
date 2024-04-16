<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

abstract class AbstractConfirmType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                // These are the values that are outputted to the HTML (i.e. the form->getData() will return us a
                // bool, but the <input value> will be "yes" or "no")
                'choice_value' => fn(?bool $b) => match($b) {
                    true => "yes",
                    false => "no",
                    null => ""
                },
                'constraints' => new NotNull(['message' => $options['null_message']]),
            ]))
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'field_options' => [
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
            ],
            'null_message' => 'common.choice.not-null',
        ]);
        $resolver->setRequired([
            'translation_key_prefix',
        ]);
    }
}
