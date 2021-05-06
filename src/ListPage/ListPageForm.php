<?php

namespace App\ListPage;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
            $fieldName = $field->getId();
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

            $builder->add($fieldName, $type, array_merge($fieldOptions, [
                'label' => "{$field->getLabel()} (filter)",
                'label_attr' => ['class' => 'govuk-visually-hidden'],
                'attr' => [
                    'placeholder' => $field->getLabel(),
                    'class' => 'filter',
                ],
            ]));
        }

        $builder
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Apply<span class="govuk-visually-hidden"> filters</span>',
                'label_html' => true,
            ])
            ->add('clear', Gds\ButtonType::class, [
                'label' => 'Clear<span class="govuk-visually-hidden"> filters</span>',
                'label_html' => true,
                'attr' => [
                    'class' => 'govuk-button--warning',
                ]
            ])
            ->add('orderBy', HiddenType::class)
            ->add('orderDirection', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'get',
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);

        $resolver->setRequired('fields');
        $resolver->setAllowedTypes('fields', Field::class.'[]');
    }

    public function getBlockPrefix()
    {
        return null;
    }
}