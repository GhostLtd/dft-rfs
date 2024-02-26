<?php

namespace App\Form\Admin\DomesticSurvey\Edit;

use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\BusinessDetailsType as EmbeddedBusinessDetailsType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessAndVehicleDetailsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('business_details', EmbeddedBusinessDetailsType::class, [
                'inherit_data' => true,
                'label' => false,
                'expanded_employees' => false,
                'add_form' => true,
                'validation_groups' => $options['validation_groups'],
            ])
            ->add('vehicle_details', VehicleDetailsType::class, [
                'inherit_data' => true,
                'label' => false,
                'include_buttons' => false,
                'add_form' => true,
                'validation_groups' => $options['validation_groups'],
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Save changes',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['admin_vehicle', 'admin_business_details'],
        ]);
    }
}
