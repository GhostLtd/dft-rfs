<?php

namespace App\Form\Domestic;

use App\Entity\DomesticSurveyResponse;
use App\Form\WorkflowChoiceFormInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompletableStatusType extends AbstractType implements WorkflowChoiceFormInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('can_complete', Gds\ChoiceType::class, [
                'choices' => ['Yes' => 'yes', 'No' => 'no'],
                'expanded' => true,
                'label' => 'Will you be able to complete this survey?',
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'label_is_page_heading' => true,
                'help' => 'For example, you may no longer own this vehicle or it may be on hire.'
            ])
        ;
    }
}
