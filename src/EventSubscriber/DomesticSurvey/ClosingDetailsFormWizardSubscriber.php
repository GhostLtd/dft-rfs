<?php


namespace App\EventSubscriber\DomesticSurvey;


use App\Workflow\DomesticSurvey\ClosingDetailsState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\WorkflowInterface;

class ClosingDetailsFormWizardSubscriber implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        $prefix = 'workflow.domestic_survey_closing_details';
        return [
            // Transition events
            "{$prefix}.transition.finish" => 'transitionFinish',
        ];
    }


    public function transitionFinish(Event $event): void
    {
        $stateObject = $this->getStateObject($event);
        $survey = $stateObject->getSubject()->getSurvey();
        if ($this->domesticSurveyStateMachine->can($survey, 'complete'))
        {
            $this->domesticSurveyStateMachine->apply($survey, 'complete');
        }
    }

    public function __construct(
        /**
         * Needed to close the domestic survey
         */
        private WorkflowInterface $domesticSurveyStateMachine
    )
    {
    }

    /**
     * @return ClosingDetailsState | object
     */
    protected function getStateObject(Event $event)
    {
        return $event->getSubject();
    }
}