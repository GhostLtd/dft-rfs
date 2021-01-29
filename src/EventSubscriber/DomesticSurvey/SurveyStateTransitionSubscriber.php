<?php


namespace App\EventSubscriber\DomesticSurvey;


use Alphagov\Notifications\Client;
use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use App\Utility\AlphagovNotify\TemplateReference;
use App\Utility\RegistrationMarkHelper;
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

        $personalisation = [
            'registrationMark' => (new RegistrationMarkHelper($survey->getRegistrationMark()))->getFormattedRegistrationMark(),
            'startDate' => $survey->getSurveyPeriodStart()->format('l, jS F Y'),
            'endDate' => $survey->getSurveyPeriodEnd()->format('l, jS F Y'),
            'passcode1' => $survey->getPasscodeUser() ? $survey->getPasscodeUser()->getUsername() : 'unknown',
            'passcode2' => $survey->getPasscodeUser() ? $survey->getPasscodeUser()->getPlainPassword() : 'unknown',
        ];

        if ($survey->getInvitationAddress()->isFilled())
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