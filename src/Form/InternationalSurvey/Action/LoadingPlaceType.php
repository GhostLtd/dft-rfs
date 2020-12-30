<?php

namespace App\Form\InternationalSurvey\Action;

use App\Entity\AbstractGoodsDescription;
use App\Entity\International\Action;
use App\Repository\International\ActionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoadingPlaceType extends AbstractType
{
    protected $actionRepository;
    protected $requestStack;
    protected $translator;

    public function __construct(ActionRepository $actionRepository, RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->actionRepository = $actionRepository;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            /** @var Action $action */
            $action = $event->getData();

            $prefix = 'international.action.unloading';

            $event->getForm()
                ->add('loadingAction', Gds\ChoiceType::class, [
                    'label_is_page_heading' => true,
                    'label' => "{$prefix}.label",
                    'label_attr' => ['class' => 'govuk-label--xl'],
                    'help' => "{$prefix}.help",
                    'choices' => $this->getChoicesForPlace($action->getTrip()->getId()),
                ]);
        });
    }

    protected function getChoicesForPlace(string $tripId): array
    {
        $actions = $this->actionRepository->getLoadingActions($tripId);

        $choices = [];
        foreach ($actions as $action) {
            $choices[$this->getLabelForLoadingAction($action)] = $action;
        }

        return $choices;
    }

    protected function getLabelForLoadingAction(Action $action): string
    {
        $country = Countries::getName(strtoupper($action->getCountry()), $this->requestStack->getCurrentRequest()->getLocale());

        $goods = $action->getGoodsDescription() === AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER ?
                $action->getGoodsDescriptionOther() :
                $this->translator->trans("goods.description.options.{$action->getGoodsDescription()}");

        return $this->translator->trans('international.action.stop', [
            'place' => $action->getName(),
            'country' => $country,
            'number' => $action->getNumber(),
            'goods' => $goods,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
            'validation_groups' => ['action-loading-place'],
        ]);
    }
}
