<?php

namespace App\Form\DomesticSurvey\InitialDetails;

use App\Entity\DomesticSurveyResponse;
use App\Form\WorkflowChoiceFormInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UnableToCompleteOnHireType extends AbstractType implements WorkflowChoiceFormInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('unableToCompleteReason', Gds\ChoiceType::class, [
                'choices' => ['Yes' => 'on-hire', 'No' => null],
                'label' => 'Will your vehicle be on hire during the survey period?',
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'help' => 'For example, you may no longer own this vehicle or it may be on hire.',
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
