<?php

namespace App\Form;

use App\Entity\Address;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lines', Gds\FieldsetType::class, ['label' => false])
            ->add('postcode', Gds\InputType::class)
        ;

        $builder->get('lines')
            ->add('line1', Gds\InputType::class, ['label' => 'Building and street'])
            ->add('line2', Gds\InputType::class, ['label' => false])
            ->add('line3', Gds\InputType::class, ['label' => 'Town or city'])
            ->add('line4', Gds\InputType::class, ['label' => 'County'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
