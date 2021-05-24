<?php

namespace App\EventSubscriber;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\Domestic\PdfHelper as DomesticPdfHelper;
use App\Utility\International\PdfHelper as InternationalPdfHelper;
use App\Utility\AlphagovNotify\Reference;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\Event;

class SurveyStateTransitionSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;
    private DomesticPdfHelper $domesticPdfHelper;
    private InternationalPdfHelper $internationalPdfHelper;

    private const TYPE_LETTER = 'letter';
    private const TYPE_EMAIL = 'email';
    private const TEMPLATE_MAP = [
        DomesticSurvey::class => [
            self::TYPE_LETTER => Reference::LETTER_DOMESTIC_SURVEY_INVITE,
            self::TYPE_EMAIL => Reference::EMAIL_DOMESTIC_SURVEY_INVITE,
        ],
        InternationalSurvey::class => [
            self::TYPE_LETTER => Reference::LETTER_INTERNATIONAL_SURVEY_INVITE,
            self::TYPE_EMAIL => Reference::EMAIL_INTERNATIONAL_SURVEY_INVITE,
        ],
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus,
        DomesticPdfHelper $domesticPdfHelper,
        InternationalPdfHelper $internationalPdfHelper
    ) {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->domesticPdfHelper = $domesticPdfHelper;
        $this->internationalPdfHelper = $internationalPdfHelper;
    }

    public function transitionInviteUser(Event $event)
    {
        $survey = $this->getSurvey($event);
        $personalisation = PersonalisationHelper::getForEntity($survey);

        $address = $survey->getInvitationAddress();
        $addressIsFilled = $address && $address->isFilled();
        if ($addressIsFilled && self::TEMPLATE_MAP[get_class($survey)][self::TYPE_LETTER]) {
            $this->messageBus->dispatch(new Letter(
                Reference::EVENT_INVITE,
                get_class($survey),
                $survey->getId(),
                $address,
                self::TEMPLATE_MAP[get_class($survey)][self::TYPE_LETTER],
                $personalisation,
            ));
        }

        if ($survey->getInvitationEmail() && self::TEMPLATE_MAP[get_class($survey)][self::TYPE_EMAIL]) {
            $this->messageBus->dispatch(new Email(
                Reference::EVENT_INVITE,
                get_class($survey),
                $survey->getId(),
                $survey->getInvitationEmail(),
                self::TEMPLATE_MAP[get_class($survey)][self::TYPE_EMAIL],
                $personalisation,
            ));
        }
    }

    public function transitionComplete(Event $event)
    {
        $survey = $this->getSurvey($event);

        if (!$survey->getSubmissionDate()) {
            $survey->setSubmissionDate(new DateTime('now'));
            $this->entityManager->flush();

            if ($survey instanceof DomesticSurvey) {
                $this->domesticPdfHelper->generateAndUploadPdf($survey);
            } else if ($survey instanceof InternationalSurvey) {
                $this->internationalPdfHelper->generateAndUploadPdf($survey);
            }
        }
    }

    protected function getSurvey(Event $event): SurveyInterface
    {
        $survey = $event->getSubject();
        if (!$survey instanceof SurveyInterface) {
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