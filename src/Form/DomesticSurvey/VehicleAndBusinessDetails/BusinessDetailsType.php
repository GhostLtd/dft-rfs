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
        $builder
            ->add('numberOfEmployees', Gds\NumberType::class, [
                'label' => 'domestic.survey-response.business-details.number-of-employees.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-5'],
                'help' => 'domestic.survey-response.business-details.number-of-employees.help',
            ])
            ->add('businessNature', Gds\InputType::class, [
                'label' => 'domestic.survey-response.business-details.business-nature.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => 'domestic.survey-response.forms.business-details.business-nature.help',
                'attr' => ['class' => 'govuk-input--width-30']
            ])
            ->add('operationType', Gds\ChoiceType::class, [
                'label' => 'domestic.survey-response.business-details.operation-type.label',
                'property_path' => 'vehicle.operationType',
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => Vehicle::OPERATION_TYPE_CHOICES,
                'help' => 'domestic.survey-response.business-details.operation-type.help',
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
