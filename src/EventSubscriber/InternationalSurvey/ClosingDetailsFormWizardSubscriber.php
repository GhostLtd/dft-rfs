<?php

namespace App\EventSubscriber\InternationalSurvey;

use App\Workflow\InternationalSurvey\ClosingDetailsState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\WorkflowInterface;

class ClosingDetailsFormWizardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private WorkflowInterface $internationalSurveyStateMachine,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.international_survey_closing_details.transition.finish' => 'transitionFinish',
        ];
    }

    public function transitionFinish(Event $event): void
    {
        /** @var ClosingDetailsState $stateObject */
        $stateObject = $event->getSubject();
        $survey = $stateObject->getSubject();

        if ($this->internationalSurveyStateMachine->can($survey, 'complete')) {
            $this->internationalSurveyStateMachine->apply($survey, 'complete');
        }
    }
}
