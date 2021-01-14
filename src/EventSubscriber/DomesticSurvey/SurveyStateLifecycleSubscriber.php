<?php


namespace App\EventSubscriber\DomesticSurvey;


use App\Entity\Domestic\Survey;
use App\Repository\PasscodeUserRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as OrmEvents;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyStateLifecycleSubscriber implements EventSubscriber
{
    private $domesticSurveyStateMachine;
    private $entityManager;
    private $passcodeUserRepository;

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
        $entity = $args->getEntity();
        if (!$entity instanceof Survey) {
            return;
        }

        // it's a new survey
        $entity->setState(Survey::STATE_NEW);
        if (!$entity->getPasscodeUser()) {
            $entity->setPasscodeUser($this->passcodeUserRepository->createNewPasscodeUser());
        }
        if (!$entity->getReminderState()) {
            $entity->setReminderState(Survey::REMINDER_STATE_INITIAL);
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
//            $this->entityManager->flush();
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