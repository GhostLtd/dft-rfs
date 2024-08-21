<?php

namespace App\EventListener\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsDoctrineListener(event: Events::prePersist)]
class SurveyResponseStateLifecycleListener
{
    public function __construct(protected WorkflowInterface $internationalSurveyStateMachine)
    {}

    public function prePersist(PrePersistEventArgs $args): void
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
}
