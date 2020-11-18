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
            ->add('lines', Gds\FieldsetType::class, [
                'label' => false,
            ])
        ;

        $builder->get('lines')
            ->add('line1', Gds\InputType::class, [
                'label' => 'Building and street',
            ])
            ->add('line2', Gds\InputType::class, [
                'label' => false,
            ])
            ->add('line3', Gds\InputType::class, [
                'label' => 'Town or city',
                'attr' => [
                    'class' => 'govuk-!-width-two-thirds',
                ],
            ])
            ->add('line4', Gds\InputType::class, [
                'label' => 'County',
                'attr' => [
                    'class' => 'govuk-!-width-two-thirds',
                ],
            ])
            ->add('postcode', Gds\InputType::class, [
                'attr' => [
                    'class' => 'govuk-input--width-10',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Address::class,
            'label_is_page_heading' => false,
        ]);

        $resolver->setAllowedTypes('label_is_page_heading', ['bool']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['label_is_page_heading'] = $options['label_is_page_heading'];
    }
}
