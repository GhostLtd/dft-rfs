<?php

namespace App\Form\InternationalSurvey\InitialDetails;

use App\Entity\International\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $prefix = "international.survey-response.business-details";
        $employeesPrefix = "{$prefix}.fewer-than-ten-employees";
        $naturePrefix = "{$prefix}.business-nature";

        $builder
            ->add('fewerThanTenEmployees', Gds\ChoiceType::class, [
                'label' => "{$employeesPrefix}.label",
                'help' => "{$employeesPrefix}.help",
                'choices' => [
                    "{$employeesPrefix}.choices.yes" => true,
                    "{$employeesPrefix}.choices.no" => false,
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('businessNature', Gds\InputType::class, [
                'label' => "{$naturePrefix}.label",
                'help' => "{$naturePrefix}.help",
                'attr' => ['class' => 'govuk-input--width-30'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['business_details'],
        ]);
    }
}
