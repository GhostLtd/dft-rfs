<?php

namespace App\Form\Admin\RoRo;

use App\Entity\RoRo\OperatorGroup;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OperatorGroupType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', InputType::class, [
                'label' => 'Name',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'add' => false,
            'data_class' => OperatorGroup::class,
        ]);

        $resolver->setAllowedTypes('add', 'bool');

        $resolver->setDefault('validation_groups', function(Options $options) {
            $groups = $options['add'] ? 'admin_add_operator_group' : 'admin_edit_operator_group';
            return [$groups];
        });
    }
}
