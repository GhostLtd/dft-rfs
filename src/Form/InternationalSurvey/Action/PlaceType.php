<?php

namespace App\Form\InternationalSurvey\Action;

use App\Entity\International\Action;
use App\Form\CountryType;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class PlaceType extends AbstractType
{
    protected ?string $actionType; // Passed to the template via buildView(). Used to change the page titles.

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $prefix = "international.action.place";
        $this->actionType = null;

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options, $prefix) {
                /** @var Action $action */
                $action = $event->getData();
                $form = $event->getForm();

                if ($action->getId() === null) {
                    /** @var Action[]|Collection $loadingActions */
                    $loadingActions = $action->getTrip()->getActions()->filter(fn(Action $a) => $a->getLoading());

                    $allFullyUnloaded = true;
                    foreach ($loadingActions as $loadingAction) {
                        $unloadingActions = $loadingAction->getUnloadingActions();

                        if ($unloadingActions->count() === 1) {
                            /** @var Action $firstUnloadingAction */
                            $firstUnloadingAction = $unloadingActions->first();

                            if (!$firstUnloadingAction->getWeightUnloadedAll()) {
                                $allFullyUnloaded = false;
                                break;
                            }
                        } else {
                            // If it has zero unloadings, it's not unloaded.
                            // If it has more than one, then we've not flagged to "unload all", but rather are unloading partially.
                            $allFullyUnloaded = false;
                            break;
                        }
                    }

                    $choiceOptions = [];
                    if ($loadingActions->count() === 0) {
                        $choiceOptions["{$prefix}.loading.choices.unload"] = [
                            'disabled' => true,
                            'help' => "{$prefix}.loading.no-goods-loaded",
                        ];
                    } else if ($allFullyUnloaded) {
                        $choiceOptions["{$prefix}.loading.choices.unload"] = [
                            'disabled' => true,
                            'help' => "{$prefix}.loading.all-goods-fully-loaded",
                        ];
                    }

                    $form->add('loading', Gds\ChoiceType::class, [
                        'label' => "{$prefix}.loading.label",
                        'help' => "{$prefix}.loading.help",
                        'expanded' => true,
                        'multiple' => false,
                        'choices' => [
                            "{$prefix}.loading.choices.load" => true,
                            "{$prefix}.loading.choices.unload" => false,
                        ],
                        'choice_options' => $choiceOptions,
                        'label_attr' => ['class' => 'govuk-label--m'],
                    ]);
                }

                $isLoading = $action->getLoading();
                $placeLabel = "{$prefix}.place.label";

                if ($isLoading !== null) {
                    if ($isLoading) {
                        $this->actionType = 'loading';
                        $placeLabel .= '-load';
                    } else {
                        $this->actionType = 'unloading';
                        $placeLabel .= '-unload';
                    }
                }

                $form
                    ->add('place', Gds\FieldsetType::class, [
                        'label' => $placeLabel,
                        'label_attr' => ['class' => 'govuk-label--m'],
                    ]);
                $form->get('place')
                    ->add('name', Gds\InputType::class, [
                        'label' => "{$prefix}.name.label",
                        'help' => "{$prefix}.name.help",
                        'attr' => ['class' => 'govuk-input--width-10'],
                        'label_attr' => ['class' => 'govuk-label--s'],
                    ])
                    ->add('country', CountryType::class, [
                        'country_label_attr' => ['class' => 'govuk-label--m'],
                    ]);
            });
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['actionType'] = $this->actionType;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
            'validation_groups' => ['action-place'],
        ]);
    }
}
