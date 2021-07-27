<?php

namespace App\Form\Admin;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractDeleteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        @trigger_error('The use of AbstractDeleteType form is deprecated. You should be using the ConfirmActionType form, with accompanying controller, instead.', E_USER_DEPRECATED);

        $translationKeyPrefix = $options['translation_key_prefix'];

        $builder
            ->add('delete', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "{$translationKeyPrefix}.delete.label",
                'attr' => ['class' => 'govuk-button--warning'],
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "{$translationKeyPrefix}.cancel.label",
                'attr' => ['class' => 'govuk-button--secondary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'admin',
        ]);
        $resolver->setRequired(['translation_key_prefix']);
    }
}