<?php

namespace App\EventSubscriber\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as OrmEvents;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyResponseStateLifecycleSubscriber implements EventSubscriber
{
    private WorkflowInterface $internationalSurveyStateMachine;

    public function __construct(WorkflowInterface $internationalSurveyStateMachine)
    {
        $this->internationalSurveyStateMachine = $internationalSurveyStateMachine;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $response = $args->getObject();
        if (!$response instanceof SurveyResponse) {
            return;
        }

        $survey = $response->getSurvey();

        if ($this->internationalSurveyStateMachine->can($survey, 'started')) {
            $this->internationalSurveyStateMachine->apply($survey, 'started');
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            OrmEvents::prePersist => 'prePersist',
        ];
    }
}