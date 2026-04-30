<?php

namespace App\EventSubscriber\DomesticSurvey;

use App\Workflow\DomesticSurvey\ClosingDetailsState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\WorkflowInterface;

class ClosingDetailsFormWizardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private WorkflowInterface $domesticSurveyStateMachine,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.domestic_survey_closing_details.transition.finish' => 'transitionFinish',
        ];
    }

    public function transitionFinish(Event $event): void
    {
        /** @var ClosingDetailsState $stateObject */
        $stateObject = $event->getSubject();
        $survey = $stateObject->getSubject()->getSurvey();
        if ($this->domesticSurveyStateMachine->can($survey, 'complete')) {
            $this->domesticSurveyStateMachine->apply($survey, 'complete');
        }
    }
}
