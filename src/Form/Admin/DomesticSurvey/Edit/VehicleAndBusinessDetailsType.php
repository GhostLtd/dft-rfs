<?php


namespace App\Form\Admin\DomesticSurvey\Edit;


use App\Entity\Domestic\SurveyResponse;
use App\Entity\Vehicle;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\BusinessDetailsType;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\VehicleWeightsType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleAndBusinessDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('business_details', BusinessDetailsType::class, [
                'inherit_data' => true,
                'label' => false,
                'expanded_employees' => false,
            ])
            ->add('weights', VehicleWeightsType::class, [
                'inherit_data' => true,
                'label' => false,
            ])
            ->add('axleConfiguration', Gds\ChoiceType::class, [
                'label' => "Type/configuration",
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => [
                    'Rigid' => Vehicle::AXLE_CONFIGURATION_CHOICES[100],
                    'Rigid with trailer' => Vehicle::AXLE_CONFIGURATION_CHOICES[200],
                    'Articulated' => Vehicle::AXLE_CONFIGURATION_CHOICES[300],
                ],
                'expanded' => false,
                'attr' => ['class' => 'govuk-select--width-15'],
                'property_path' => 'vehicle.axleConfiguration',
            ])
            ->add('bodyType', Gds\ChoiceType::class, [
                'label' => 'Vehicle/trailer body type',
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => Vehicle::BODY_CONFIGURATION_CHOICES,
                'expanded' => false,
                'attr' => ['class' => 'govuk-select--width-15'],
                'property_path' => 'vehicle.bodyType',
            ])

            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
        ]);
    }
}