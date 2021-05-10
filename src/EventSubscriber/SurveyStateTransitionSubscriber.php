<?php

namespace App\EventSubscriber;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use App\Entity\SurveyTrait;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AlphagovNotify\TemplateReference;
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

    private const TYPE_LETTER = 'letter';
    private const TYPE_EMAIL = 'email';
    private const TEMPLATE_MAP = [
        DomesticSurvey::class => [
            self::TYPE_LETTER => TemplateReference::LETTER_DOMESTIC_SURVEY_INVITE,
            self::TYPE_EMAIL => TemplateReference::EMAIL_DOMESTIC_SURVEY_INVITE,
        ],
        InternationalSurvey::class => [
            self::TYPE_LETTER => TemplateReference::LETTER_INTERNATIONAL_SURVEY_INVITE,
            self::TYPE_EMAIL => TemplateReference::EMAIL_INTERNATIONAL_SURVEY_INVITE,
        ],
    ];

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
        $survey = $this->getSurvey($event);
        $personalisation = PersonalisationHelper::getForEntity($survey);

        $address = $survey->getInvitationAddress();
        $addressIsFilled = $address && $address->isFilled();
        if ($addressIsFilled) {
            $this->messageBus->dispatch(new Letter(
                get_class($survey),
                $survey->getId(),
                $address,
                self::TEMPLATE_MAP[get_class($survey)][self::TYPE_LETTER],
                $personalisation,
            ));
        }

        if ($survey->getInvitationEmail()) {
            $this->messageBus->dispatch(new Email(
                get_class($survey),
                $survey->getId(),
                $survey->getInvitationEmail(),
                self::TEMPLATE_MAP[get_class($survey)][self::TYPE_EMAIL],
                $personalisation,
            ));
        }

        if ($survey->getInvitationEmail() || $addressIsFilled) {
            $survey->setNotifiedDate(new DateTime());
        }
    }

    public function transitionComplete(Event $event)
    {
        $survey = $this->getSurvey($event);

        if (!$survey->getSubmissionDate()) {
            $survey->setSubmissionDate(new DateTime('now'));
            $this->entityManager->flush();
        }
    }

    protected function getSurvey(Event $event): SurveyInterface
    {
        /** @var SurveyInterface $survey */
        $survey = $event->getSubject();
        if (!in_array(get_class($survey), [DomesticSurvey::class, InternationalSurvey::class])) {
            throw new \LogicException("unhandled survey class: ". get_class($survey));
        }
        return $survey;
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.domestic_survey.transition.invite_user' => 'transitionInviteUser',
            'workflow.domestic_survey.transition.complete' => 'transitionComplete',
            'workflow.international_survey.transition.invite_user' => 'transitionInviteUser',
            'workflow.international_survey.transition.complete' => 'transitionComplete',
        ];
    }
}