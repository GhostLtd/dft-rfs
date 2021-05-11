<?php

namespace App\Form;

use App\Entity\Address;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('line1', Gds\InputType::class, [
                'label' => 'common.address.line1',
                'block_prefix' => 'address_line1'
            ])
            ->add('line2', Gds\InputType::class, [
                'label' => 'common.address.line2',
                'block_prefix' => 'address_line2'
            ])
            ->add('line3', Gds\InputType::class, [
                'label' => 'common.address.line3',
                'attr' => [
                    'class' => 'govuk-!-width-two-thirds',
                ],
                'block_prefix' => 'address_line3'
            ])
            ->add('line4', Gds\InputType::class, [
                'label' => 'common.address.line4',
                'attr' => [
                    'class' => 'govuk-!-width-two-thirds',
                ],
                'block_prefix' => 'address_line4'
            ])
            ->add('postcode', Gds\InputType::class, [
                'label' => 'common.address.line5',
                'attr' => [
                    'class' => 'govuk-input--width-10',
                ],
                'block_prefix' => 'address_postcode'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Address::class,
            'label_is_page_heading' => false,
            'inherit_data' => false,
        ]);

        $resolver->setAllowedTypes('label_is_page_heading', ['bool']);
    }

    public function getParent()
    {
        return Gds\FieldsetType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['label_is_page_heading'] = $options['label_is_page_heading'];
    }
}
