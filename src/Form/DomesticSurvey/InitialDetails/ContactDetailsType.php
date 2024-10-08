<?php

namespace App\Form\DomesticSurvey\InitialDetails;

use App\Entity\Domestic\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactDetailsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contactBusinessName', Gds\InputType::class, [
                'label' => 'domestic.survey-response.contact-details.business-name.label',
                'help' => 'domestic.survey-response.contact-details.business-name.help',
                'attr' => ['class' => 'govuk-input--width-20'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('contactName', Gds\InputType::class, [
                'label' => 'domestic.survey-response.contact-details.contact-name.label',
                'help' => 'domestic.survey-response.contact-details.contact-name.help',
                'attr' => ['class' => 'govuk-input--width-20'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('contactTelephone', Gds\InputType::class, [
                'label' => 'domestic.survey-response.contact-details.contact-telephone.label',
                'help' => 'domestic.survey-response.contact-details.contact-telephone.help',
                'attr' => ['class' => 'govuk-input--width-20'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('contactEmail', Gds\EmailType::class, [
                'label' => 'domestic.survey-response.contact-details.contact-email.label',
                'help' => 'domestic.survey-response.contact-details.contact-email.help',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => 'contact_details',
        ]);
    }
}
