<?php

namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\SurveyResponse as AbstractSurveyResponse;
use App\Entity\Vehicle;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessDetailsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $employeeChoices = AbstractSurveyResponse::EMPLOYEES_CHOICES;

        if ($options['add_form']) {
            $employeeChoices = ['' => null] + $employeeChoices;
        }

        $builder
            ->add('numberOfEmployees', Gds\ChoiceType::class, [
                'choices' => $employeeChoices,
                'label' => "domestic.survey-response.business-details.number-of-employees.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "domestic.survey-response.business-details.number-of-employees.help",
                'expanded' => $options['expanded_employees'],
            ])
            ->add('businessNature', Gds\InputType::class, [
                'label' => "domestic.survey-response.business-details.business-nature.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "domestic.survey-response.business-details.business-nature.help",
                'attr' => ['class' => 'govuk-input--width-30']
            ])
            ->add('operationType', Gds\ChoiceType::class, [
                'label' => "domestic.survey-response.business-details.operation-type.label",
                'property_path' => 'vehicle.operationType',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'choices' => Vehicle::OPERATION_TYPE_CHOICES,
                'help' => "domestic.survey-response.business-details.operation-type.help",
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['business_details', 'vehicle_operation_type'],
            'expanded_employees' => true,
            'add_form' => false,
        ]);
    }
}
