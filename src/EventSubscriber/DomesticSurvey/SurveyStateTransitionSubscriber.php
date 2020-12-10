<?php


namespace App\EventSubscriber\DomesticSurvey;


use App\Entity\PasscodeUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyStateTransitionSubscriber implements EventSubscriberInterface
{
    /**
     * @var WorkflowInterface
     */
    private $domesticSurveyStateMachine;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(WorkflowInterface $domesticSurveyStateMachine, EntityManagerInterface $entityManager)
    {
        $this->domesticSurveyStateMachine = $domesticSurveyStateMachine;
        $this->entityManager = $entityManager;
    }

    public function transitionInviteUser(Event $event)
    {
        // ToDo: invite the user to take part
        if (function_exists('dump')) {
            dump('ToDo: invite domestic user');
        }
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof PasscodeUser || !$user->hasRole(PasscodeUser::ROLE_DOMESTIC_SURVEY_USER)) {
            return;
        }

        if ($this->domesticSurveyStateMachine->can($user->getDomesticSurvey(), 'started')) {
            $this->domesticSurveyStateMachine->apply($user->getDomesticSurvey(), 'started');
            $this->entityManager->flush();
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.domestic_survey.transition.invite_user' => 'transitionInviteUser',
            'security.interactive_login' => 'onInteractiveLogin',
        ];
    }
}