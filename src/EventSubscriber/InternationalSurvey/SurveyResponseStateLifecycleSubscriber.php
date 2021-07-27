<?php

namespace App\EventSubscriber\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Repository\PasscodeUserRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as OrmEvents;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyResponseStateLifecycleSubscriber implements EventSubscriber
{
    private WorkflowInterface $internationalSurveyStateMachine;
    private EntityManagerInterface $entityManager;
    private PasscodeUserRepository $passcodeUserRepository;

    public function __construct(
        WorkflowInterface $internationalSurveyStateMachine,
        EntityManagerInterface $entityManager,
        PasscodeUserRepository $passcodeUserRepository
    ) {
        $this->internationalSurveyStateMachine = $internationalSurveyStateMachine;
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

        if ($this->internationalSurveyStateMachine->can($survey, 'started')) {
            $this->internationalSurveyStateMachine->apply($survey, 'started');
        }
    }

    public function getSubscribedEvents()
    {
        return [
            OrmEvents::prePersist => 'prePersist',
        ];
    }
}