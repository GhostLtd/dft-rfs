<?php

namespace App\Form\DomesticSurvey\InitialDetails;

use App\Entity\Domestic\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InPossessionOfVehicleType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isInPossessionOfVehicle', Gds\ChoiceType::class, [
                'choices' => SurveyResponse::IN_POSSESSION_CHOICES,
                'label' => "domestic.survey-response.in-possession-of-vehicle.is-in-possession-of-vehicle.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--l'],
                'help' => "domestic.survey-response.in-possession-of-vehicle.is-in-possession-of-vehicle.help",
                'choice_options' => [
                    SurveyResponse::IN_POSSESSION_TRANSLATION_PREFIX.SurveyResponse::IN_POSSESSION_YES => [
                        'conditional_form_name' => 'isExemptVehicleType',
                    ],
                ],
            ])
            ->add('isExemptVehicleType', Gds\ChoiceType::class, [
                'choices' => SurveyResponse::IS_EXEMPT_CHOICES,
                'label' => "domestic.survey-response.is-exempt-vehicle-type.label",
                'help' => "domestic.survey-response.is-exempt-vehicle-type.help",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => function(FormInterface $form) {
                $data = $form->getData();
                $groups = ['domestic.in-possession'];

                assert($data instanceof SurveyResponse);

                if ($data->getIsInPossessionOfVehicle() === SurveyResponse::IN_POSSESSION_YES) {
                    $groups[] = 'domestic.is-exempt-vehicle-type';
                }

                return $groups;
            },
        ]);
    }
}
