<?php

namespace App\Form\Domestic;

use App\Entity\DomesticSurveyResponse;
use App\Form\WorkflowChoiceFormInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OnHireStatusType extends AbstractType implements WorkflowChoiceFormInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('is_on_hire', Gds\ChoiceType::class, [
                'choices' => ['Yes' => 'on-hire', 'No' => false],
                'expanded' => true,
                'label' => 'Will your vehicle be on hire during the survey period?',
                'label_is_page_heading' => true,
                'help' => 'For example, you may no longer own this vehicle or it may be on hire.',
            ])
        ;
    }
}
