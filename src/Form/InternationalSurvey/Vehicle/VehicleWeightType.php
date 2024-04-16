<?php

namespace App\Form\InternationalSurvey\Vehicle;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class VehicleWeightType extends AbstractType
{
    protected string $translationKeyPrefix = "international.vehicle.vehicle-weight";

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('grossWeight', Gds\IntegerType::class, [
                'label' => "{$this->translationKeyPrefix}.gross-weight.label",
                'help' => "{$this->translationKeyPrefix}.gross-weight.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
            ->add('carryingCapacity', Gds\IntegerType::class, [
                'label' => "{$this->translationKeyPrefix}.carrying-capacity.label",
                'help' => "{$this->translationKeyPrefix}.carrying-capacity.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['vehicle_weight'],
        ]);
    }
}
