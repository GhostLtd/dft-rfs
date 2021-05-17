<?php


namespace App\Form\Admin;


use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MaintenanceWarningType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'gds_datetime';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('start', Gds\DateTimeType::class)
            ->add('end', Gds\DateTimeType::class)
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
