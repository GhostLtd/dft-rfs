<?php

namespace App\Form\Extension;

use App\Utility\Form\TonnesToKilogramsNormaliser;
use Ghost\GovUkFrontendBundle\Form\Type\IntegerType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Opt-in: when 'convert_tonnes' is true, converts an explicitly tonnes-denominated value (e.g.
 * "3.8 T", "3,5 TON") to kilograms before it reaches the integer transformer. See
 * {@see TonnesToKilogramsNormaliser} for the (deliberately conservative) rules.
 */
class ConvertTonnesExtension extends AbstractTypeExtension
{
    public function __construct(private readonly TonnesToKilogramsNormaliser $normaliser)
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
        $resolver->setDefault('convert_tonnes', false);
        $resolver->setAllowedTypes('convert_tonnes', 'bool');
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['convert_tonnes'] !== true) {
            return;
        }

        // Priority 30: runs before unit (20) and separator (10) stripping, since it owns the
        // decimal interpretation of a comma/dot when a tonnes unit is present.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (is_string($data)) {
                $event->setData($this->normaliser->normalise($data));
            }
        }, 30);
    }
}
