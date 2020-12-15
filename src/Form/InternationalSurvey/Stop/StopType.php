<?php

namespace App\Form\InternationalSurvey\Stop;

use App\Entity\International\Stop;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StopType extends AbstractType
{
    protected $locale;

    public function __construct(RequestStack $requestStack)
    {
        $this->locale = $requestStack->getCurrentRequest()->getLocale();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $prefix = "international.stop.stop";

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
                'choices' => array_flip(Countries::getNames($options['choice_translation_locale'])),
                'expanded' => false,
                'placeholder' => '',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'accessible-autocomplete govuk-input--width-10'],
            ])->add('save', Gds\ButtonType::class, [
                'label' => 'common.actions.save-and-continue',
                'type' => 'submit',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Stop::class,
            'choice_translation_locale' => $this->locale,
        ]);
    }
}
