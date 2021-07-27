<?php

namespace App\Form;

use App\Entity\LongAddress;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LongAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lines', Gds\FieldsetType::class, [
                'label' => false,
            ]);

        $fieldSet = $builder->get('lines');

        if ($options['include_addressee']) {
            $fieldSet
                ->add('line1', Gds\InputType::class, [
                    'label' => 'Addressee',
                    'attr' => [
                        'class' => $options['line_class'],
                    ],
                ]);
        }

        $fieldSet
            ->add('line2', Gds\InputType::class, [
                'label' => 'Line 1',
                'attr' => [
                    'class' => $options['line_class'],
                ],
            ])
            ->add('line3', Gds\InputType::class, [
                'label' => 'Line 2',
                'attr' => [
                    'class' => $options['line_class'],
                ],
            ])
            ->add('line4', Gds\InputType::class, [
                'label' => 'Line 3',
                'attr' => [
                    'class' => $options['line_class'],
                ],
            ])
            ->add('line5', Gds\InputType::class, [
                'label' => 'Line 4',
                'attr' => [
                    'class' => $options['line_class'],
                ],
            ])
            ->add('line6', Gds\InputType::class, [
                'label' => 'Line 5',
                'attr' => [
                    'class' => $options['line_class'],
                ],
            ])
            ->add('postcode', Gds\InputType::class, [
                'attr' => [
                    'class' => 'govuk-input--width-10',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => LongAddress::class,
            'label_is_page_heading' => false,
            'line_class' => 'govuk-!-width-two-thirds',

            // needed in order to add violations to the root
            'error_bubbling' => false,
            'include_addressee' => true,
        ]);

        $resolver->setAllowedTypes('label_is_page_heading', ['bool']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['label_is_page_heading'] = $options['label_is_page_heading'];
    }
}
