<?php

namespace App\Form\InternationalSurvey\Action;

use App\Entity\International\Action;
use App\Utility\LoadingPlaceHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class LoadingPlaceType extends AbstractType
{
    protected $loadingPlaceHelper;

    public function __construct(LoadingPlaceHelper $loadingPlaceHelper)
    {
        $this->loadingPlaceHelper = $loadingPlaceHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            /** @var Action $action */
            $action = $event->getData();

            $prefix = 'international.action.unloading';

            [$choices, $choiceOptions] = $this->loadingPlaceHelper->getChoicesAndOptionsForPlace($action);

            $event->getForm()
                ->add('loadingAction', Gds\ChoiceType::class, [
                    'label_is_page_heading' => true,
                    'label' => "{$prefix}.label",
                    'label_attr' => ['class' => 'govuk-label--xl'],
                    'help' => "{$prefix}.help",
                    'choices' => $choices,
                    'choice_options' => $choiceOptions,
                ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
            'validation_groups' => ['action-loading-place'],
        ]);
    }
}
