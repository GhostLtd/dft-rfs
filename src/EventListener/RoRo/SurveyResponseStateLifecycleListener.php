<?php

namespace App\EventListener\RoRo;

use App\Entity\RoRo\Survey;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsDoctrineListener(event: Events::preUpdate)]
class SurveyResponseStateLifecycleListener
{
    public function __construct(
        protected WorkflowInterface $roroSurveyStateMachine,
        protected Security          $security
    ) {}

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $survey = $args->getObject();
        if (!$survey instanceof Survey) {
            return;
        }

        if (
            !$this->security->isGranted('ROLE_RORO_USER')
            && !$this->security->isGranted('ROLE_ADMIN_USER')
        ) {
            return;
        }

        if ($this->roroSurveyStateMachine->can($survey, 'started')) {
            $this->roroSurveyStateMachine->apply($survey, 'started');
        }
    }
}
