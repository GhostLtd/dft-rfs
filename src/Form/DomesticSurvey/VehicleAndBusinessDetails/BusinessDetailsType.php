<?php

namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Vehicle;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.survey-response.business-details";
        $builder
            ->add('numberOfEmployees', Gds\ChoiceType::class, [
                'choices' => SurveyResponse::EMPLOYEES_CHOICES,
                'label' => "{$translationKeyPrefix}.number-of-employees.label",
                'label_attr' => ['class' => 'govuk-label--s'],
//                'attr' => ['class' => 'govuk-input--width-5'],
                'help' => "{$translationKeyPrefix}.number-of-employees.help",
            ])
            ->add('businessNature', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.business-nature.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.business-nature.help",
                'attr' => ['class' => 'govuk-input--width-30']
            ])
            ->add('operationType', Gds\ChoiceType::class, [
                'label' => "{$translationKeyPrefix}.operation-type.label",
                'property_path' => 'vehicle.operationType',
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => Vehicle::OPERATION_TYPE_CHOICES,
                'help' => "{$translationKeyPrefix}.operation-type.help",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['business_details', 'vehicle_operation_type'],
        ]);
    }
}
