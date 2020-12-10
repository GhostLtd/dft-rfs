<?php


namespace App\EventSubscriber\DomesticSurvey;


use App\Workflow\DomesticSurvey\ClosingDetailsState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\WorkflowInterface;

class ClosingDetailsFormWizardSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        $prefix = 'workflow.domestic_survey_closing_details';
        return [
            // Guard events
            "{$prefix}.guard.not_empty_survey" => 'guardNotEmptySurvey',
            "{$prefix}.guard.empty_survey" => 'guardEmptySurvey',

            // Transition events
            "{$prefix}.transition.finish" => 'transitionFinish',
        ];
    }




    public function transitionFinish(Event $event)
    {
        $stateObject = $this->getStateObject($event);
        $survey = $stateObject->getSubject()->getSurvey();
        if ($this->domesticSurveyStateMachine->can($survey, 'complete'))
        {
            $this->domesticSurveyStateMachine->apply($survey, 'complete');
        }
    }

    /**
     * Transition from Vehicle Fuel to No Journeys
     * @param GuardEvent $event
     */
    public function guardEmptySurvey(GuardEvent $event)
    {
        $stateObject = $this->getStateObject($event);
        if ($stateObject->getSubject()->hasJourneys()) {
            $event->setBlocked(true);
        }
    }

    /**
     * Transition from Vehicle Fuel to Confirmation
     * @param GuardEvent $event
     */
    public function guardNotEmptySurvey(GuardEvent $event)
    {
        $stateObject = $this->getStateObject($event);
        if (!$stateObject->getSubject()->hasJourneys()) {
            $event->setBlocked(true);
        }
    }






    /**
     * @var WorkflowInterface
     */
    private $domesticSurveyStateMachine;

    public function __construct(WorkflowInterface $domesticSurveyStateMachine)
    {
        $this->domesticSurveyStateMachine = $domesticSurveyStateMachine;
    }

    /**
     * @param Event $event
     * @return ClosingDetailsState | object
     */
    protected function getStateObject(Event $event)
    {
        return $event->getSubject();
    }
}