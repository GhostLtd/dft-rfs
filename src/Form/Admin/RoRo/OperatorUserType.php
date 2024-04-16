<?php

namespace App\Form\Admin\RoRo;

use App\Entity\RoRoUser;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\EmailType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OperatorUserType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', EmailType::class, [
                'label' => 'Email address',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('submit', ButtonType::class, [
                'type' => 'submit',
                'label' => 'Save',
            ])
            ->add('cancel', ButtonType::class, [
                'type' => 'submit',
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RoRoUser::class,
        ]);

        $resolver->setDefault('validation_groups', 'admin_add_roro_user');
    }
}
