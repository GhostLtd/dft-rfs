<?php

namespace App\Form\InternationalSurvey\Action;

use App\Entity\AbstractGoodsDescription;
use App\Entity\International\Action;
use App\Form\InternationalSurvey\Action\DataMapper\GoodsUnloadedWeightDataMapper;
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

class GoodsUnloadedWeightType extends AbstractType
{
    protected $actionRepository;
    protected $requestStack;
    protected $translator;

    const CHOICE_YES = 'common.choices.boolean.yes';
    const CHOICE_NO = 'common.choices.boolean.no';
    const CHOICES = [
        self::CHOICE_YES => true,
        self::CHOICE_NO => false,
    ];

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator, ActionRepository $actionRepository)
    {
        $this->actionRepository = $actionRepository;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setDataMapper(new GoodsUnloadedWeightDataMapper());
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            /** @var Action $action */
            $action = $event->getData();

            // Fetching the loadingAction like this means its unloadingActions will be correctly populated...
            $loadingAction = $this->actionRepository->findOneByIdAndSurveyResponse($action->getLoadingAction()->getId(), $action->getTrip()->getVehicle()->getSurveyResponse());

            $country = Countries::getName(strtoupper($loadingAction->getCountry()), $this->requestStack->getCurrentRequest()->getLocale());

            $goods = $loadingAction->getGoodsDescription() === AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER ?
                $loadingAction->getGoodsDescriptionOther() :
                $this->translator->trans("goods.description.options.{$loadingAction->getGoodsDescription()}");

            $prefix = "international.action.goods-weight-unloaded";
            $weightPrefix = "{$prefix}.weight-of-goods";
            $unloadedAllPrefix = "{$prefix}.unloaded-all";

            $form = $event->getForm();

            $unloadingActions = $loadingAction->getUnloadingActions();

            $numberOfOtherUnloadingActions = 0;
            foreach($unloadingActions as $unloadingAction) {
                if ($unloadingAction->getId() !== $action->getId()) {
                    $numberOfOtherUnloadingActions++;
                }
            }

            if ($numberOfOtherUnloadingActions === 0) {
                $form
                    ->add("weightUnloadedAll", Gds\ChoiceType::class, [
                        'label' => "{$unloadedAllPrefix}.label",
                        'help' => "{$unloadedAllPrefix}.help",
                        'label_translation_parameters' => [
                            'weight' => $loadingAction->getWeightOfGoods(),
                            'place' => $loadingAction->getName(),
                            'country' => $country,
                            'goods' => $goods,
                        ],
                        'label_attr' => ['class' => 'govuk-label--s'],
                        'choices' => self::CHOICES,
                        'choice_options' => [
                            self::CHOICE_NO => [
                                'conditional_form_name' => 'weightOfGoods',
                            ],
                        ],
                    ]);
            }

            $form
                ->add('weightOfGoods', Gds\NumberType::class, [
                    'label' => "{$weightPrefix}.label",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'help' => "{$weightPrefix}.help",
                    'attr' => ['class' => 'govuk-input--width-10'],
                    'suffix' => 'kg',
                ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
            'validation_groups' => ['action-unloaded-weight'],
        ]);
    }
}
