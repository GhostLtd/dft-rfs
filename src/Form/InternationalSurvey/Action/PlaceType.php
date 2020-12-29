<?php

namespace App\Form\InternationalSurvey\Action;

use App\Entity\International\Action;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class PlaceType extends AbstractType
{
    protected $locale;

    public function __construct(RequestStack $requestStack)
    {
        $this->locale = $requestStack->getCurrentRequest()->getLocale();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $prefix = "international.action.place";

        $builder
            ->add('name', Gds\InputType::class, [
                'label' => "{$prefix}.name.label",
                'help' => "{$prefix}.name.help",
                'attr' => ['class' => 'govuk-input--width-10'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('country', Gds\ChoiceType::class, [
                // TODO: Hook locale
                // TODO: Sort choices: 1) prioritising those mentioned in TransittedCountries 2) TransittedCountriesOther
                'label' => "{$prefix}.country.label",
                'help' => "{$prefix}.country.help",
                'choices' => array_flip(Countries::getNames($options['choice_translation_locale'])),
                'expanded' => false,
                'placeholder' => '',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'accessible-autocomplete govuk-input--width-10'],
            ])
            ->add('loading', Gds\ChoiceType::class, [
                'label' => "{$prefix}.loading.label",
                'help' => "{$prefix}.loading.help",
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    "{$prefix}.loading.choices.load" => true,
                    "{$prefix}.loading.choices.unload" => false,
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
            'choice_translation_locale' => $this->locale,
            'validation_groups' => ['action-place'],
        ]);
    }
}
