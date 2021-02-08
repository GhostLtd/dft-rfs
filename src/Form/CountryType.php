<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CountryType extends AbstractType
{
    public const OTHER = '0';
    public const COUNTRY_CHOICES = ['GB','FR','BE','NL','DE','IE','IT','ES','CH','LU'];

    protected string $locale;
    protected string $other;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->locale = $requestStack->getCurrentRequest()->getLocale();
        $this->other = $translator->trans('common.choices.other');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('country', Gds\ChoiceType::class, [
                'label' => $options['country_label'],
                'label_attr' => $options['country_label_attr'],
                'help' => $options['country_help'],
                'choices' => $this->getCountryChoices($options['choice_translation_locale']),
                'expanded' => true,
                'attr' => $options['country_attr'],
                'choice_options' => [
                    $this->other => [
                        'conditional_form_name' => 'country_other',
                    ],
                ],
            ])
            ->add('country_other', Gds\InputType::class, [
                'label' => $options['other_label'],
                'label_attr' => $options['other_label_attr'],
                'help' => $options['other_help'],
                'attr' => $options['other_attr'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choice_translation_locale' => $this->locale,
            'label' => false,

            'country_label' => 'common.country.country.label',
            'country_help' => 'common.country.country.help',
            'country_label_attr' => ['class' => 'govuk-label--s'],
            'country_attr' => [],

            'other_label' => 'common.country.other.label',
            'other_help' => 'common.country.other.help',
            'other_label_attr' => ['class' => 'govuk-label--s'],
            'other_attr' => [],

            'inherit_data' => true,
        ]);
    }

    protected function getCountryChoices(string $locale): array
    {
        $choices = [];

        foreach(self::COUNTRY_CHOICES as $code) {
            $choices[Countries::getName($code, $locale)] = $code;
        }

        $choices[$this->other] = self::OTHER;
        return $choices;
    }
}