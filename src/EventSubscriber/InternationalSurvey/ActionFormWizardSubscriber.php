<?php

namespace App\EventSubscriber\InternationalSurvey;

use App\Workflow\InternationalSurvey\ActionState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\WorkflowInterface;

class ActionFormWizardSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        $prefix = 'workflow.international_survey_action';
        return [
            // Guard events
            "{$prefix}.guard.goods_loaded" => 'guardGoodsLoaded',
            "{$prefix}.guard.goods_unloaded" => 'guardGoodsUnloaded',

            "{$prefix}.guard.finish_edit_loaded" => 'editingExisting',
            "{$prefix}.guard.weight_of_goods_entered" => 'creatingNew',

            "{$prefix}.guard.finish_edit_unloaded" => 'editingExisting',
            "{$prefix}.guard.unloaded_weight_entered" => 'creatingNew',
        ];
    }


    /**
     * Transition from "Place" to "Goods description"
     * @param GuardEvent $event
     */
    public function guardGoodsLoaded(GuardEvent $event)
    {
        $stateObject = $this->getStateObject($event);
        if (!$stateObject->getSubject()->getLoading()) {
            $event->setBlocked(true);
        }
    }

    /**
     * Transition from "Place" to "Consignment unloaded"
     * @param GuardEvent $event
     */
    public function guardGoodsUnloaded(GuardEvent $event)
    {
        $stateObject = $this->getStateObject($event);
        if ($stateObject->getSubject()->getLoading()) {
            $event->setBlocked(true);
        }
    }

    public function editingExisting(GuardEvent $event)
    {
        $stateObject = $this->getStateObject($event);
        if (!$stateObject->getSubject()->getId()) {
            $event->setBlocked(true);
        }
    }

    public function creatingNew(GuardEvent $event)
    {
        $stateObject = $this->getStateObject($event);
        if ($stateObject->getSubject()->getId()) {
            $event->setBlocked(true);
        }
    }


    /**
     * @var WorkflowInterface
     */
    private $internationalSurveyActionStateMachine;

    public function __construct(WorkflowInterface $internationalSurveyActionStateMachine)
    {
        $this->internationalSurveyActionStateMachine = $internationalSurveyActionStateMachine;
    }

    /**
     * @param Event $event
     * @return ActionState | object
     */
    protected function getStateObject(Event $event)
    {
        return $event->getSubject();
    }
}