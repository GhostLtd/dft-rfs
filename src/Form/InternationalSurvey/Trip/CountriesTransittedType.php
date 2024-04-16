<?php

namespace App\Form\InternationalSurvey\Trip;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountriesTransittedType extends AbstractType
{
    public const COUNTRY_CHOICES = ['FR','BE','NL','DE','IE','IT','ES','CH','LU','AT'];

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('countriesTransitted', Gds\ChoiceType::class, [
                'choices' => $this->getCountryChoices(),
                'help' => "international.trip.countries-transitted.countries-transitted.help",
                'label' => "international.trip.countries-transitted.countries-transitted.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('countriesTransittedOther', Gds\InputType::class, [
                'help' => "international.trip.countries-transitted.countries-transitted-other.help",
                'label' => "international.trip.countries-transitted.countries-transitted-other.label",
                'label_attr' => ['class' => 'govuk-label--s'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['trip_countries_transitted'],
        ]);
    }

    protected function getCountryChoices(): array
    {
        $choices = [];

        foreach(self::COUNTRY_CHOICES as $code) {
            $choices[Countries::getName($code)] = $code;
        }

        return $choices;
    }
}
