<?php

namespace App\Form\RoRo;

use App\Entity\RoRo\Survey;
use App\Entity\RoRo\VehicleCount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class VehicleCountsType extends AbstractType
{
    public function __construct(protected TranslatorInterface $translator)
    {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Survey $data */
        $data = $builder->getData();
        $unaccompaniedTrailerId = $data->getOtherVehicleCounts()
            ->filter(fn(VehicleCount $p) => $p->getOtherCode() === 'trailers')
            ->first()
            ->getId();

        $builder
            ->add('countryVehicleCounts', CollectionType::class, [
                'attr' => ['class' => 'govuk-!-margin-bottom-5'],
                'label' => 'roro.survey.vehicle-counts.country.label',
                'label_attr' => ['class' => 'govuk-label--l'],
                'entry_type' => VehicleCountEntryType::class,
            ])
            ->add('otherVehicleCounts', CollectionType::class, [
                'attr' => ['class' => 'govuk-!-margin-bottom-5'],
                'label' => 'roro.survey.vehicle-counts.other.label',
                'label_attr' => ['class' => 'govuk-label--l'],
                'entry_type' => VehicleCountEntryType::class,
                'entry_options' => [
                    // N.B. See VehicleCountEntryType.php (both about help_options, and the use of translator here)
                    'help_options' => [
                        $unaccompaniedTrailerId => $this->translator->trans('roro.survey.vehicle-counts.unaccompanied-trailers.help'),
                    ],
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'validation_groups' => 'roro.vehicle-counts',
        ]);
    }
}