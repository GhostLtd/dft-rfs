<?php

namespace App\Form\DomesticSurvey\InitialDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Form\AddressType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Validator\Constraints\NotNull;

class SoldDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('soldDate', Gds\DateType::class, [
                'label' => 'domestic.survey-response.sold-details.date.label',
                'help' => 'domestic.survey-response.sold-details.date.help',
                'label_attr' => ['class' => 'govuk-label--s'],
                'constraints' => [
                    new NotNull([
                    'message' => "common.date.not-null",
                    'groups' => ['admin_sold'],
                    ])
                ],
                'property_path' => $options['date_property_path'],
            ])
            ->add('newOwnerName', Gds\InputType::class, [
                'label' => 'domestic.survey-response.sold-details.new-owner-name.label',
                'help' => 'domestic.survey-response.sold-details.new-owner-name.help',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('newOwnerEmail', Gds\EmailType::class, [
                'label' => 'domestic.survey-response.sold-details.new-owner-email.label',
                'help' => 'domestic.survey-response.sold-details.new-owner-email.help',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('newOwnerAddress', AddressType::class, [
                'label' => 'domestic.survey-response.sold-details.new-owner-address.label',
                'help' => 'domestic.survey-response.sold-details.new-owner-address.help',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => 'sold_details',
            'date_property_path' => 'unableToCompleteDate',
        ]);
    }
}
