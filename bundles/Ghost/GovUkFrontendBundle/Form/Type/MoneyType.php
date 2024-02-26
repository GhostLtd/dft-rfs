<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\CostTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoneyType extends AbstractType
{
    #[\Override]
    public function getParent(): ?string
    {
        return InputType::class;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->addViewTransformer(new CostTransformer($options['divisor']));
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'gds_money';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'compound' => false,
            'divisor' => 100,
            'prefix' => '£',
        ]);
    }
}
