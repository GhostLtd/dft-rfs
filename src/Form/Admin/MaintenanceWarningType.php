<?php

namespace App\Form\Admin;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MaintenanceWarningType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start', Gds\DateTimeType::class,[
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => null,
                'time_options' => [
                    'expanded' => false,
                ],
            ])
            ->add('end', Gds\TimeType::class, [
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => null,
                'expanded' => false,
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Save changes',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ])
            ;
    }
}
