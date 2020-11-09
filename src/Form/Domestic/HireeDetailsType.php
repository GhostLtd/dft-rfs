<?php

namespace App\Form\Domestic;

use App\Entity\DomesticSurveyResponse;
use App\Form\AddressType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HireeDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('hireeName', Gds\InputType::class)
            ->add('hireeEmail', Gds\EmailType::class)
            ->add('hireeAddress', AddressType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DomesticSurveyResponse::class,
            'validation_groups' => ['hiree_details', 'address'],
        ]);
    }
}
