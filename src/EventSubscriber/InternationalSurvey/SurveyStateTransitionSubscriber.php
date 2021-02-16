<?php


namespace App\EventSubscriber\InternationalSurvey;


use App\Entity\International\Survey;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use App\Utility\AlphagovNotify\TemplateReference;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyStateTransitionSubscriber implements EventSubscriberInterface
{
    private WorkflowInterface $internationalSurveyStateMachine;
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;
    private string $frontendHostname;

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
        /** @var Survey $survey */
        $survey = $event->getSubject();

        $personalisation = [
            'startDate' => $survey->getSurveyPeriodStart()->format('l, jS F Y'),
            'endDate' => $survey->getSurveyPeriodEnd()->format('l, jS F Y'),
            'passcode1' => $survey->getPasscodeUser() ? $survey->getPasscodeUser()->getUsername() : 'unknown',
            'passcode2' => $survey->getPasscodeUser() ? $survey->getPasscodeUser()->getPlainPassword() : 'unknown',
        ];

        $address = $survey->getInvitationAddress();
        $addressIsFilled = $address && $address->isFilled();
        if ($addressIsFilled)
        {
            $this->messageBus->dispatch(new Letter(
                Survey::class,
                $survey->getId(),
                $address,
                TemplateReference::LETTER_INTERNATIONAL_SURVEY_INVITE,
                $personalisation,
            ));
        }

        if ($survey->getInvitationEmail()) {
            $this->messageBus->dispatch(new Email(
                Survey::class,
                $survey->getId(),
                $survey->getInvitationEmail(),
                TemplateReference::EMAIL_INTERNATIONAL_SURVEY_INVITE,
                $personalisation,
            ));
        }

        if ($survey->getInvitationEmail() || $addressIsFilled) {
            $survey->setNotifiedDate(new DateTime());
        }
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

    public static function getSubscribedEvents()
    {
        return [
            'workflow.international_survey.transition.invite_user' => 'transitionInviteUser',
            'workflow.international_survey.transition.complete' => 'transitionComplete',
        ];
    }
}