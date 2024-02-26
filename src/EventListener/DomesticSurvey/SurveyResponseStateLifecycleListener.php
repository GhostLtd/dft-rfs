<?php

namespace App\EventListener\DomesticSurvey;

use App\Entity\Domestic\SurveyResponse;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsDoctrineListener(event: Events::prePersist)]
class SurveyResponseStateLifecycleListener
{
    public function __construct(protected WorkflowInterface $domesticSurveyStateMachine)
    {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $response = $args->getObject();
        if (!$response instanceof SurveyResponse) {
            return;
        }

        $survey = $response->getSurvey();

        if ($this->domesticSurveyStateMachine->can($survey, 'started')) {
            $this->domesticSurveyStateMachine->apply($survey, 'started');
        }
    }
}
