<?php

namespace App\EventSubscriber\DomesticSurvey;

use App\Entity\Domestic\SurveyResponse;
use App\Repository\PasscodeUserRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as OrmEvents;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyResponseStateLifecycleSubscriber implements EventSubscriber
{
    private WorkflowInterface $domesticSurveyStateMachine;
    private EntityManagerInterface $entityManager;
    private PasscodeUserRepository $passcodeUserRepository;

    public function __construct(
        WorkflowInterface $domesticSurveyStateMachine,
        EntityManagerInterface $entityManager,
        PasscodeUserRepository $passcodeUserRepository
    ) {
        $this->domesticSurveyStateMachine = $domesticSurveyStateMachine;
        $this->entityManager = $entityManager;
        $this->passcodeUserRepository = $passcodeUserRepository;
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