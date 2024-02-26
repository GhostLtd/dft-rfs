<?php

namespace App\EventListener\InternationalSurvey;

use App\Entity\International\Survey;
use App\Entity\SurveyStateInterface;
use App\Utility\PasscodeGenerator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::postPersist)]
class SurveyStateLifecycleSubscriber
{
    public function __construct(
        protected WorkflowInterface $internationalSurveyStateMachine,
        protected EntityManagerInterface $entityManager,
        protected PasscodeGenerator $passcodeGenerator
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Survey) {
            return;
        }

        // it's a new survey
        if (!$entity->getState()) {
            $entity->setState(SurveyStateInterface::STATE_NEW);
        }

        if (!$entity->getPasscodeUser()) {
            $entity->setPasscodeUser($this->passcodeGenerator->createNewPasscodeUser());
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Survey) {
            return;
        }

        if ($this->internationalSurveyStateMachine->can($entity, 'invite_user')) {
            $this->internationalSurveyStateMachine->apply($entity, 'invite_user');
            $this->entityManager->flush();
        }
    }
}
