<?php

namespace App\EventSubscriber\InternationalSurvey;

use App\Workflow\InternationalSurvey\ClosingDetailsState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\WorkflowInterface;

class ClosingDetailsFormWizardSubscriber implements EventSubscriberInterface
{
    private WorkflowInterface $internationalSurveyStateMachine;

    public function __construct(WorkflowInterface $internationalSurveyStateMachine)
    {
        $this->internationalSurveyStateMachine = $internationalSurveyStateMachine;
    }

    public static function getSubscribedEvents()
    {
        $prefix = 'workflow.international_survey_closing_details';
        return [
            // Transition events
            "{$prefix}.transition.finish" => 'transitionFinish',
        ];
    }

    public function transitionFinish(Event $event)
    {
        $stateObject = $this->getStateObject($event);
        $survey = $stateObject->getSubject();

        if ($this->internationalSurveyStateMachine->can($survey, 'complete'))
        {
            $this->internationalSurveyStateMachine->apply($survey, 'complete');
        }
    }

    protected function getStateObject(Event $event): ClosingDetailsState
    {
        return $event->getSubject();
    }
}