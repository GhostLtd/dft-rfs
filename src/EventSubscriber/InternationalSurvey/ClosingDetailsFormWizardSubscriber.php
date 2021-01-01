<?php

namespace App\EventSubscriber\InternationalSurvey;

use App\Workflow\InternationalSurvey\ClosingDetailsState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\WorkflowInterface;

class ClosingDetailsFormWizardSubscriber implements EventSubscriberInterface
{
    private $internationalSurveyStateMachine;

    public function __construct(WorkflowInterface $internationalSurveyStateMachine)
    {
        $this->internationalSurveyStateMachine = $internationalSurveyStateMachine;
    }

    public static function getSubscribedEvents()
    {
        $prefix = 'workflow.international_survey_closing_details';
        return [
            // Guard events
            "{$prefix}.guard.filled_out" => 'guardFilledOut',
            "{$prefix}.guard.not_filled_out" => 'guardNotFilledOut',

            // Transition events
            "{$prefix}.transition.finish" => 'transitionFinish',
        ];
    }

    public function transitionFinish(Event $event)
    {
        $stateObject = $this->getStateObject($event);
        $survey = $stateObject->getSubject()->getSurvey();

        if ($this->internationalSurveyStateMachine->can($survey, 'complete'))
        {
            $this->internationalSurveyStateMachine->apply($survey, 'complete');
        }
    }

    public function guardFilledOut(GuardEvent $event)
    {
        $stateObject = $this->getStateObject($event);
        if (!$stateObject->getSubject()->isFilledOut()) {
            $event->setBlocked(true);
        }
    }

    public function guardNotFilledOut(GuardEvent $event)
    {
        $stateObject = $this->getStateObject($event);
        if ($stateObject->getSubject()->isFilledOut()) {
            $event->setBlocked(true);
        }
    }

    protected function getStateObject(Event $event): ClosingDetailsState
    {
        return $event->getSubject();
    }
}