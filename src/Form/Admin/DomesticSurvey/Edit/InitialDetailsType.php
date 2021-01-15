<?php


namespace App\Form\Admin\DomesticSurvey\Edit;


use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\InitialDetails\ContactDetailsType;
use App\Form\DomesticSurvey\InitialDetails\HireeDetailsType;
use App\Form\DomesticSurvey\InitialDetails\InPossessionOfVehicleType;
use App\Form\DomesticSurvey\InitialDetails\ScrappedDetailsType;
use App\Form\DomesticSurvey\InitialDetails\SoldDetailsType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InitialDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contact_details', ContactDetailsType::class, [
                'inherit_data' => true,
                'label_attr' => ['class' => 'govuk-label--m']
            ])
            ->add('isInPossessionOfVehicle', Gds\ChoiceType::class, [
                'choices' => SurveyResponse::IN_POSSESSION_CHOICES,
                'label' => "domestic.survey-response.in-possession-of-vehicle.is-in-possession-of-vehicle.label",
                'choice_options' => [
                    SurveyResponse::IN_POSSESSION_TRANSLATION_PREFIX . SurveyResponse::IN_POSSESSION_ON_HIRE => ['conditional_form_name' => 'hiree_details'],
                    SurveyResponse::IN_POSSESSION_TRANSLATION_PREFIX . SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN => ['conditional_form_name' => 'scrapped_details'],
                    SurveyResponse::IN_POSSESSION_TRANSLATION_PREFIX . SurveyResponse::IN_POSSESSION_SOLD => ['conditional_form_name' => 'sold_details'],
                ],
            ])

            ->add('hiree_details', HireeDetailsType::class, [
                'label' => false,
                'inherit_data' => true,
            ])
            ->add('sold_details', SoldDetailsType::class, [
                'label' => false,
                'inherit_data' => true,
            ])
            ->add('scrapped_details', ScrappedDetailsType::class, [
                'label' => false,
                'is_child_form' => true,
                'inherit_data' => true,
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