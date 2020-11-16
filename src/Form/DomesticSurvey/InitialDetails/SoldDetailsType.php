<?php

namespace App\Form\DomesticSurvey\InitialDetails;

use App\Entity\DomesticSurveyResponse;
use App\Form\AddressType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class SoldDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('unableToCompleteDate', Gds\DateType::class, [
                'label' => 'When was the vehicle sold',
            ])
            ->add('newOwnerName', Gds\InputType::class)
            ->add('newOwnerEmail', Gds\EmailType::class)
            ->add('newOwnerAddress', AddressType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DomesticSurveyResponse::class,
            'validation_groups' => 'sold_details'
        ]);
    }
}
