<?php

namespace App\Form\Admin\RoRo;

use App\Form\RoRo\VehicleCountsType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EditType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
    public function getParent(): string
    {
        return VehicleCountsType::class;
    }
}
