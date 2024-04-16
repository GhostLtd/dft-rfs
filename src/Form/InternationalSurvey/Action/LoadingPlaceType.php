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
    public function __construct(protected LoadingPlaceHelper $loadingPlaceHelper)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Action $action */
            $action = $event->getData();

            [$choices, $choiceOptions] = $this->loadingPlaceHelper->getChoicesAndOptionsForPlace($action, true);

            $event->getForm()
                ->add('loadingAction', Gds\ChoiceType::class, [
                    'label_is_page_heading' => true,
                    'label' => "international.action.unloading.label",
                    'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                    'help' => "international.action.unloading.help",
                    'choices' => $choices,
                    'choice_options' => $choiceOptions,
                    'choice_value' => fn(?Action $a) => $a ? "action-{$a->getNumber()}" : null,
                ]);
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
            'validation_groups' => ['action-loading-place'],
        ]);
    }
}
