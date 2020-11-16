<?php

namespace App\Form\DomesticSurvey\InitialDetails;

use App\Entity\DomesticSurveyResponse;
use App\Form\WorkflowChoiceFormInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbleToCompleteType extends AbstractType implements WorkflowChoiceFormInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ableToComplete', Gds\ChoiceType::class, [
                'choices' => ['Yes' => true, 'No' => false],
                'label' => 'Will you be able to complete this survey?',
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'label_is_page_heading' => true,
                'help' => 'For example, you may no longer own this vehicle or it may be on hire.'
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
