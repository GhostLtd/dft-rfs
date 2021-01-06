<?php

namespace App\ListPage;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListPageForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var $field Field
         */
        foreach($options['fields'] as $field) {
            $parameterName = $field->getParameterName();
            $fieldType = $field->getType();

            if ($fieldType === null) {
                continue;
            }

            switch($fieldType) {
                case Field::TYPE_TEXT:
                    $type = Gds\InputType::class;
                    $fieldOptions = [];
                    break;
                case Field::TYPE_SELECT:
                    $type = Gds\ChoiceType::class;
                    $fieldOptions = [
                        'placeholder' => $field->getLabel(),
                        'choices' => $field->getChoices(),
                        'expanded' => false,
                    ];
                    break;
                default:
                    throw new InvalidArgumentException('Invalid field type');
            }

            $builder->add($parameterName, $type, array_merge($fieldOptions, [
                'label' => false,
                'attr' => [
                    'placeholder' => $field->getLabel(),
                    'class' => 'filter',
                ],
            ]));
        }

        $builder->add('buttons', null, [
            'compound' => true,
        ]);

        $builder->get('buttons')
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Apply',
            ])
            ->add('clear', Gds\ButtonType::class, [
                'label' => 'Clear',
                'attr' => [
                    'class' => 'govuk-button--warning',
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'get',
        ]);

        $resolver->setRequired('fields');
        $resolver->setAllowedTypes('fields', Field::class.'[]');
    }

    public function getBlockPrefix()
    {
        return 'filter';
    }
}