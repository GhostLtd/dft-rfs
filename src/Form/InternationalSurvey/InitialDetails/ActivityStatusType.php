<?php

namespace App\Form\InternationalSurvey\InitialDetails;

use App\Entity\International\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('activityStatus', Gds\ChoiceType::class, [
                'label' => SurveyResponse::ACTIVITY_STATUS_PREFIX.'activity-status.label',
                'help' => SurveyResponse::ACTIVITY_STATUS_PREFIX.'activity-status.help',
                'choices' => SurveyResponse::ACTIVITY_STATUS_CHOICES,
                'label_is_page_heading' => true,
                'label_attr' => [
                    'class' => 'govuk-label--l',
                ]
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
