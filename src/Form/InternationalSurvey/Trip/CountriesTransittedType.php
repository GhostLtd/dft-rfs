<?php

namespace App\Form\InternationalSurvey\Trip;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountriesTransittedType extends AbstractType
{
    const COUNTRY_CHOICES = ['FR','ES','NL','IE','NO','BE','DE','DK','PT','AT'];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = 'international.trip.countries-transitted';

        $builder
            ->add('countriesTransitted', Gds\ChoiceType::class, [
                'choices' => $this->getCountryChoices(),
                'help' => "{$translationKeyPrefix}.countries-transitted.help",
                'label' => "{$translationKeyPrefix}.countries-transitted.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('countriesTransittedOther', Gds\InputType::class, [
                'help' => "{$translationKeyPrefix}.countries-transitted-other.help",
                'label' => "{$translationKeyPrefix}.countries-transitted-other.label",
                'label_attr' => ['class' => 'govuk-label--s'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
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