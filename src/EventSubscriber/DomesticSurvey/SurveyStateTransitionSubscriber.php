<?php


namespace App\EventSubscriber\DomesticSurvey;


use Alphagov\Notifications\Client;
use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use App\Utility\AlphagovNotify\TemplateReference;
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
    private $domesticSurveyStateMachine;

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
        WorkflowInterface $domesticSurveyStateMachine,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus,
        $frontendHostname
    ) {
        $this->domesticSurveyStateMachine = $domesticSurveyStateMachine;
        $this->entityManager = $entityManager;
        $this->frontendHostname = $frontendHostname;
        $this->messageBus = $messageBus;
    }

    public function transitionInviteUser(Event $event)
    {
        /** @var Survey $survey */
        $survey = $event->getSubject();
        $personalisation =                 [
            'invitation_link' => "http://{$this->frontendHostname}/login",
            'passcode1' => $survey->getPasscodeUser()->getUsername(),
            'passcode2' => $survey->getPasscodeUser()->getPlainPassword(),
        ];

        if ($survey->getInvitationAddress())
        {
            $this->messageBus->dispatch(new Letter(
                Survey::class,
                $survey->getId(),
                $survey->getInvitationAddress(),
                TemplateReference::LETTER_DOMESTIC_SURVEY_INVITE,
                $personalisation,
            ));
        }

        if ($survey->getInvitationEmail()) {
            $this->messageBus->dispatch(new Email(
                Survey::class,
                $survey->getId(),
                $survey->getInvitationEmail(),
                TemplateReference::EMAIL_DOMESTIC_SURVEY_INVITE,
                $personalisation,
            ));
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