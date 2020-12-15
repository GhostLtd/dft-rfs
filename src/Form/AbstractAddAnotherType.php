<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

abstract class AbstractAddAnotherType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('add_another', ChoiceType::class, [
                'mapped' => false,
                'label' => "{$options['translation_key_prefix']}.add-another.label",
                'help' => "{$options['translation_key_prefix']}.add-another.help",
                'label_attr' => ['class' => 'govuk-label--xl'],
                'choices' => [
                    'common.choices.boolean.yes' => true,
                    'common.choices.boolean.no' => false,
                ],
                'constraints' => new NotNull(['message' => 'common.choice.not-null']),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
        ]);
        $resolver->setRequired([
            'translation_key_prefix',
        ]);
    }
}
