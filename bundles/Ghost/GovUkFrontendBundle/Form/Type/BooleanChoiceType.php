<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use RuntimeException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanChoiceType extends AbstractType
{
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_boolean_choice';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($property) {
                return match ($property) {
                    null => null,
                    false => "false",
                    true => "true",
                    default => throw new RuntimeException("unexpected property value: $property"),
                };
            },
            function ($property) {
                return match ($property) {
                    null => null,
                    "false" => false,
                    "true" => true,
                    default => throw new RuntimeException("unexpected property value: $property"),
                };
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'expanded' => true,
            'choices' => [
                'common.choices.boolean.yes' => "true",
                'common.choices.boolean.no' => "false",
            ],
            'choice_translation_domain' => 'messages',
        ]);
    }
}
