<?php


namespace App\EventSubscriber\InternationalSurvey;


use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyStateTransitionSubscriber implements EventSubscriberInterface
{
    /**
     * @var WorkflowInterface
     */
    private $internationalSurveyStateMachine;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    private $frontendHostname;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(
        WorkflowInterface $internationalSurveyStateMachine,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus,
        $frontendHostname
    )
    {
        $this->internationalSurveyStateMachine = $internationalSurveyStateMachine;
        $this->entityManager = $entityManager;
        $this->frontendHostname = $frontendHostname;
        $this->messageBus = $messageBus;
    }

    public function transitionInviteUser(Event $event)
    {
        // TODO
    }

    public function transitionComplete(Event $event)
    {
        /** @var Survey $survey */
        $survey = $event->getSubject();

        if (!$survey->getSubmissionDate()) {
            $survey->setSubmissionDate(new DateTime('now'));
            $this->entityManager->flush();
        }
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof PasscodeUser || !$user->hasRole(PasscodeUser::ROLE_INTERNATIONAL_SURVEY_USER)) {
            return;
        }

        if ($this->internationalSurveyStateMachine->can($user->getInternationalSurvey(), 'started')) {
            $this->internationalSurveyStateMachine->apply($user->getInternationalSurvey(), 'started');
            $this->entityManager->flush();
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.international_survey.transition.invite_user' => 'transitionInviteUser',
            'workflow.international_survey.transition.complete' => 'transitionComplete',
            'security.interactive_login' => 'onInteractiveLogin',
        ];
    }
}