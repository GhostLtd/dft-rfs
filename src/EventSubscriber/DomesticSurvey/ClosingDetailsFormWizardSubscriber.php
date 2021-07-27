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
     * Needed to close the domestic survey
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