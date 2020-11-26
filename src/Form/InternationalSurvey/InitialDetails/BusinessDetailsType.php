<?php

namespace App\Form\InternationalSurvey\InitialDetails;

use App\Entity\International\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessDetailsType extends AbstractType
{
    const PREFIX = "international.survey-response.business-details";
    const EMPLOYEES_PREFIX = self::PREFIX . '.fewer-than-ten-employees';
    const NATURE_PREFIX = self::PREFIX . '.business-nature';

    const EMPLOYEES_CHOICES = [
        self::EMPLOYEES_PREFIX.".choices.yes" => true,
        self::EMPLOYEES_PREFIX.".choices.no" => false,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fewerThanTenEmployees', Gds\ChoiceType::class, [
                'label' => self::EMPLOYEES_PREFIX.".label",
                'help' => self::EMPLOYEES_PREFIX.".help",
                'choices' => self::EMPLOYEES_CHOICES,
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('businessNature', Gds\InputType::class, [
                'label' => self::NATURE_PREFIX.".label",
                'help' => self::NATURE_PREFIX.".help",
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
