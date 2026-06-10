<?php

namespace App\Form\Extension;

use App\Utility\Form\UnitStripper;
use Ghost\GovUkFrontendBundle\Form\Type\IntegerType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StripUnitsExtension extends AbstractTypeExtension
{
    public function __construct(protected readonly UnitStripper $unitStripper)
    {
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [IntegerType::class];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('strip_units', []);
        $resolver->setAllowedTypes('strip_units', 'string[]');
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $units = $options['strip_units'];
        if ($units === []) {
            return;
        }

        // Priority 20: runs before separator stripping (priority 10).
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($units): void {
            $data = $event->getData();
            if (is_string($data)) {
                $event->setData($this->unitStripper->strip($data, $units));
            }
        }, 20);
    }
}
