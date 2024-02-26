<?php

namespace App\EventListener\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
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
class PreEnquiryStateLifecycleSubscriber
{
    public function __construct(
        protected WorkflowInterface $preEnquiryStateMachine,
        protected EntityManagerInterface $entityManager,
        protected PasscodeGenerator $passcodeGenerator
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof PreEnquiry) {
            return;
        }

        // it's a new survey
        $entity->setState(SurveyStateInterface::STATE_NEW);
        if (!$entity->getPasscodeUser()) {
            $entity->setPasscodeUser($this->passcodeGenerator->createNewPasscodeUser());
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof PreEnquiry) {
            return;
        }

        if ($this->preEnquiryStateMachine->can($entity, 'invite_user')) {
            $this->preEnquiryStateMachine->apply($entity, 'invite_user');
            $this->entityManager->flush();
        }
    }
}
