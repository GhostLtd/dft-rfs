<?php


namespace App\EventSubscriber\InternationalSurvey;


use App\Entity\International\Survey;
use App\Repository\PasscodeUserRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as OrmEvents;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyStateLifecycleSubscriber implements EventSubscriber
{
    private $internationalSurveyStateMachine;
    private $entityManager;
    private $passcodeUserRepository;

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
        $entity = $args->getEntity();
        if (!$entity instanceof Survey) {
            return;
        }

        // it's a new survey
        $entity->setState(Survey::STATE_NEW);
        if (!$entity->getPasscodeUser()) {
            $entity->setPasscodeUser($this->passcodeUserRepository->createNewPasscodeUser());
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$entity instanceof Survey) {
            return;
        }

        if ($this->internationalSurveyStateMachine->can($entity, 'invite_user')) {
            $this->internationalSurveyStateMachine->apply($entity, 'invite_user');
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