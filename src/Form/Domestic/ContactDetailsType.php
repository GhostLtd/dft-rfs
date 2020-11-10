<?php

namespace App\Form\Domestic;

use App\Entity\DomesticSurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contactName', Gds\InputType::class)
            ->add('contactTelephone', Gds\InputType::class)
            ->add('contactEmail', Gds\EmailType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DomesticSurveyResponse::class,
            'validation_groups' => 'contact_details',
        ]);
    }
}