<?php

namespace App\Form\InternationalSurvey\InitialDetails;

use App\Entity\International\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $prefix = 'international.survey-response.contact-details';
        $builder
            ->add('contactName', Gds\InputType::class, [
                'label' => "{$prefix}.contact-name.label",
                'help' => "{$prefix}.contact-name.help",
                'label_attr' => [
                    'class' => 'govuk-label--s',
                ],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ->add('contactTelephone', Gds\InputType::class, [
                'label' => "{$prefix}.contact-telephone.label",
                'help' => "{$prefix}.contact-telephone.help",
                'label_attr' => [
                    'class' => 'govuk-label--s',
                ],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ->add('contactEmail', Gds\EmailType::class, [
                'label' => "{$prefix}.contact-email.label",
                'help' => "{$prefix}.contact-email.help",
                'label_attr' => [
                    'class' => 'govuk-label--s',
                ],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => 'contact_details',
        ]);
    }
}