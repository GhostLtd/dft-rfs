<?php

namespace App\Form\InternationalSurvey\InitialDetails;

use App\Entity\International\SurveyResponse;
use App\Entity\SurveyResponse as AbstractSurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessDetailsType extends AbstractType
{
    const PREFIX = "international.survey-response.business-details";
    const EMPLOYEES_PREFIX = self::PREFIX . '.number-of-employees';
    const NATURE_PREFIX = self::PREFIX . '.business-nature';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('numberOfEmployees', Gds\ChoiceType::class, [
                'choices' => AbstractSurveyResponse::EMPLOYEES_CHOICES,
                'label' => self::EMPLOYEES_PREFIX.".label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => self::EMPLOYEES_PREFIX.".help",
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
