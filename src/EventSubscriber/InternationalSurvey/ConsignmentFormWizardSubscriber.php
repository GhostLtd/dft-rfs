<?php


namespace App\EventSubscriber\InternationalSurvey;


use App\Workflow\DomesticSurvey\ClosingDetailsState;
use App\Workflow\InternationalSurvey\ConsignmentState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\WorkflowInterface;

class ConsignmentFormWizardSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        $prefix = 'workflow.international_survey_consignment';
        return [
            // Guard events
            "{$prefix}.guard.place_of_unloading_entered" => 'guardAddEnd',
            "{$prefix}.guard.place_of_unloading_edited" => 'guardEditEnd',
        ];
    }


    /**
     * Transition from UnloadingStop to End
     * @param GuardEvent $event
     */
    public function guardEditEnd(GuardEvent $event)
    {
        $stateObject = $this->getStateObject($event);
        if (!$stateObject->getSubject()->getId()) {
            $event->setBlocked(true);
        }
    }

    /**
     * Transition from UnloadingStop to AddAnother
     * @param GuardEvent $event
     */
    public function guardAddEnd(GuardEvent $event)
    {
        $stateObject = $this->getStateObject($event);
        if ($stateObject->getSubject()->getId()) {
            $event->setBlocked(true);
        }
    }






    /**
     * @var WorkflowInterface
     */
    private $internationalSurveyConsignmentStateMachine;

    public function __construct(WorkflowInterface $internationalSurveyConsignmentStateMachine)
    {
        $this->internationalSurveyConsignmentStateMachine = $internationalSurveyConsignmentStateMachine;
    }

    /**
     * @param Event $event
     * @return ConsignmentState | object
     */
    protected function getStateObject(Event $event)
    {
        return $event->getSubject();
    }
}