<?php

namespace App\Form\Extension;

use App\Utility\Form\ThousandsSeparatorNormaliser;
use Ghost\GovUkFrontendBundle\Form\Type\IntegerType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StripThousandsSeparatorsExtension extends AbstractTypeExtension
{
    public function __construct(protected readonly ThousandsSeparatorNormaliser $normaliser)
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
        $resolver->setDefault('strip_thousands_separators', false);
        $resolver->setAllowedTypes('strip_thousands_separators', 'bool');
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['strip_thousands_separators'] !== true) {
            return;
        }

        // Priority 10: runs after unit stripping (priority 20), so "3,500 kg" → "3,500" → "3500".
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (is_string($data)) {
                $event->setData($this->normaliser->normalise($data));
            }
        }, 10);
    }
}
