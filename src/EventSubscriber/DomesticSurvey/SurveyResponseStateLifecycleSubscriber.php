<?php

namespace App\EventSubscriber\DomesticSurvey;

use App\Entity\Domestic\SurveyResponse;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as OrmEvents;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyResponseStateLifecycleSubscriber implements EventSubscriber
{
    private WorkflowInterface $domesticSurveyStateMachine;

    public function __construct(WorkflowInterface $domesticSurveyStateMachine) {
        $this->domesticSurveyStateMachine = $domesticSurveyStateMachine;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $response = $args->getEntity();
        if (!$response instanceof SurveyResponse) {
            return;
        }

        $survey = $response->getSurvey();

        if ($this->domesticSurveyStateMachine->can($survey, 'started')) {
            $this->domesticSurveyStateMachine->apply($survey, 'started');
        }
    }

    public function getSubscribedEvents()
    {
        return [
            OrmEvents::prePersist => 'prePersist',
        ];
    }
}