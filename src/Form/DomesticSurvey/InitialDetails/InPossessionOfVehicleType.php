<?php

namespace App\Form\DomesticSurvey\InitialDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Form\WorkflowChoiceFormInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InPossessionOfVehicleType extends AbstractType implements WorkflowChoiceFormInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.survey-response.in-possession-of-vehicle";
        $builder
            ->add('isInPossessionOfVehicle', Gds\ChoiceType::class, [
                'choices' => SurveyResponse::IN_POSSESSION_CHOICES,
                'label' => "{$translationKeyPrefix}.is-in-possession-of-vehicle.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--l'],
                'help' => "{$translationKeyPrefix}.is-in-possession-of-vehicle.help",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => 'domestic.in-possession'
        ]);
    }
}
