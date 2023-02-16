<?php

namespace App\EventSubscriber\RoRo;

use App\Entity\RoRo\Survey;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as OrmEvents;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyResponseStateLifecycleSubscriber implements EventSubscriber
{
    private WorkflowInterface $roroSurveyStateMachine;
    private Security $security;

    public function __construct(WorkflowInterface $roroSurveyStateMachine, Security $security) {
        $this->roroSurveyStateMachine = $roroSurveyStateMachine;
        $this->security = $security;
    }

    public function preUpdate(LifecycleEventArgs $args)
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

    public function getSubscribedEvents(): array
    {
        return [
            OrmEvents::preUpdate => 'preUpdate',
        ];
    }
}