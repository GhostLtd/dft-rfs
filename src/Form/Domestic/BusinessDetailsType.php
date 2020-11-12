<?php

namespace App\Form\Domestic;

use App\Entity\DomesticSurveyResponse;
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
                'label' => 'survey.domestic.forms.business-details.number-of-employees.label',
                'label_attr' => ['class' => 'govuk-label--m'],
                'attr' => ['class' => 'govuk-input--width-5']
            ])
            ->add('businessNature', Gds\InputType::class, [
                'label' => 'survey.domestic.forms.business-details.business-nature.label',
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => 'survey.domestic.forms.business-details.business-nature.help',
                'attr' => ['class' => 'govuk-input--width-30']
            ])
            ->add('operationType', Gds\ChoiceType::class, [
                'label' => 'survey.domestic.forms.business-details.operation-type.label',
                'property_path' => 'vehicle.operationType',
                'label_attr' => ['class' => 'govuk-label--m'],
                'choices' => Vehicle::OPERATION_TYPE_CHOICES,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DomesticSurveyResponse::class,
        ]);
    }
}
