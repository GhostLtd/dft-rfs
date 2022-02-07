<?php


namespace App\EventSubscriber\DomesticSurvey;


use App\Entity\Domestic\Survey;
use App\Utility\PasscodeGenerator;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as OrmEvents;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyStateLifecycleSubscriber implements EventSubscriber
{
    private $domesticSurveyStateMachine;
    private $entityManager;
    private PasscodeGenerator $passcodeGenerator;

    public function __construct(
        WorkflowInterface $domesticSurveyStateMachine,
        EntityManagerInterface $entityManager,
        PasscodeGenerator $passcodeGenerator
    ) {
        $this->domesticSurveyStateMachine = $domesticSurveyStateMachine;
        $this->entityManager = $entityManager;
        $this->passcodeGenerator = $passcodeGenerator;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$entity instanceof Survey) {
            return;
        }

        // it's a new survey
        if (!$entity->getState()) {
            $entity->setState(Survey::STATE_NEW);
        }
        if (!$entity->getPasscodeUser()) {
            $entity->setPasscodeUser($this->passcodeGenerator->createNewPasscodeUser());
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$entity instanceof Survey) {
            return;
        }

        if ($this->domesticSurveyStateMachine->can($entity, 'invite_user')) {
            $this->domesticSurveyStateMachine->apply($entity, 'invite_user');
            $this->entityManager->flush();
        }
    }

    public function getSubscribedEvents()
    {
        return [
            OrmEvents::prePersist => 'prePersist',
            OrmEvents::postPersist => 'postPersist',
        ];
    }
}