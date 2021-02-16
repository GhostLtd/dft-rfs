<?php

namespace App\EventSubscriber\DomesticSurvey;

use App\Entity\Domestic\Survey;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use App\Utility\AlphagovNotify\TemplateReference;
use App\Utility\RegistrationMarkHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyStateTransitionSubscriber implements EventSubscriberInterface
{
    private WorkflowInterface $domesticSurveyStateMachine;
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;
    private string $frontendHostname;

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

        if ($survey->getInvitationEmail() || $survey->getInvitationAddress()->isFilled()) {
            $survey->setNotifiedDate(new DateTime());
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.domestic_survey.transition.invite_user' => 'transitionInviteUser',
        ];
    }
}