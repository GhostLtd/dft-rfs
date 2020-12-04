<?php

namespace App\Form\InternationalSurvey\Trip;

use App\Entity\International\Trip;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractPortsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $direction = $options['direction'];
        $prefix = "international.trip.{$direction}-ports";

        if ($direction === 'outbound') {
            $this->addPortField($builder, "{$direction}UkPort", "{$prefix}.uk-port");
            $this->addPortField($builder, "{$direction}ForeignPort", "{$prefix}.foreign-port");
        } else {
            $this->addPortField($builder, "{$direction}ForeignPort", "{$prefix}.foreign-port");
            $this->addPortField($builder, "{$direction}UkPort", "{$prefix}.uk-port");
        }
    }

    protected function addPortField(FormBuilderInterface $builder, string $fieldName, string $prefix) {
        $builder
            ->add($fieldName, Gds\InputType::class, [
                'label' => "{$prefix}.label",
                'help' => "{$prefix}.help",
                'attr' => ['class' => 'govuk-input--width-10'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'direction',
        ]);

        $resolver->setAllowedValues('direction', ['outbound', 'return']);
        $resolver->setDefaults([
            'data_class' => Trip::class,
        ]);
    }
}
