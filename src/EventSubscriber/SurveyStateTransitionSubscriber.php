<?php

namespace App\EventSubscriber;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use App\Repository\AuditLog\AuditLogRepository;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\Domestic\PdfHelper as DomesticPdfHelper;
use App\Utility\International\PdfHelper as InternationalPdfHelper;
use App\Utility\AlphagovNotify\Reference;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;

class SurveyStateTransitionSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;
    private DomesticPdfHelper $domesticPdfHelper;
    private InternationalPdfHelper $internationalPdfHelper;
    private AuditLogRepository $auditLogRepository;
    private PersonalisationHelper $personalisationHelper;

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
        PreEnquiry::class => [
            self::TYPE_LETTER => Reference::LETTER_PRE_ENQUIRY_INVITE,
            self::TYPE_EMAIL => Reference::EMAIL_PRE_ENQUIRY_INVITE,
        ],
    ];

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $messageBus, DomesticPdfHelper $domesticPdfHelper, InternationalPdfHelper $internationalPdfHelper, AuditLogRepository $auditLogRepository, PersonalisationHelper $personalisationHelper) {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->domesticPdfHelper = $domesticPdfHelper;
        $this->internationalPdfHelper = $internationalPdfHelper;
        $this->auditLogRepository = $auditLogRepository;
        $this->personalisationHelper = $personalisationHelper;
    }

    public function guardInviteUser(GuardEvent $event)
    {
        $survey = $this->getSurvey($event);
        $address = $survey->getInvitationAddress();
        if ((!$address || !$address->isFilled()) && !$survey->getInvitationEmails()) {
            $event->setBlocked(true);
        }
    }

    public function transitionInviteUser(Event $event)
    {
        $survey = $this->getSurvey($event);
        $personalisation = $this->personalisationHelper->getForEntity($survey);

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

        if ($survey->getInvitationEmails() && self::TEMPLATE_MAP[get_class($survey)][self::TYPE_EMAIL]) {

            $invitationEmails = array_map('trim', explode(',', $survey->getInvitationEmails()));

            foreach($invitationEmails as $invitationEmail) {
                $this->messageBus->dispatch(new Email(
                    Reference::EVENT_INVITE,
                    get_class($survey),
                    $survey->getId(),
                    $invitationEmail,
                    self::TEMPLATE_MAP[get_class($survey)][self::TYPE_EMAIL],
                    $personalisation,
                ));
            }
        }
    }

    public function transitionComplete(Event $event)
    {
        $survey = $this->getSurvey($event);

        if (!$survey->getSubmissionDate()) {
            $survey->setSubmissionDate(new DateTime('now'));
        }

        if ($survey instanceof DomesticSurvey) {
            $this->domesticPdfHelper->generateAndUploadPdfIfNotExists($survey);
        } else if ($survey instanceof InternationalSurvey) {
            $this->internationalPdfHelper->generateAndUploadPdfIfNotExists($survey);
        } else if ($survey instanceof PreEnquiry) {
            // Do nothing...
            return;
        } else {
            throw new \RuntimeException("Unsupported survey class".get_class($survey));
        }
    }

    public function transitionConfirmExport(Event $event)
    {
        $survey = $this->getSurvey($event);
        $this->deletePasscodeUser($survey);
    }

    public function transitionReissue(Event $event)
    {
        $survey = $this->getSurvey($event);
        $this->deletePasscodeUser($survey);
    }

    public function transitionApprove(Event $event)
    {
        $survey = $this->getSurvey($event);

        if ($survey instanceof DomesticSurvey) {
            if (!$survey->shouldAskWhyUnfilled()) {
                // ReasonForUnfilledSurvey should be NULL, because the survey *is* filled
                $survey->setReasonForUnfilledSurvey(null);

                if (!$survey->shouldAskWhyNoJourneys()) {
                    // ReasonForEmptyJourney should be NULL, because journeys *have* been entered
                    $survey->getResponse()->setReasonForEmptySurvey(null);
                }
            }
        }

        if ($survey instanceof InternationalSurvey) {
            if (!$survey->shouldAskWhyEmptySurvey()) {
                // ReasonForEmptySurvey (+Other) should be NULL, because the survey *is* filled
                $survey
                    ->setReasonForEmptySurvey(null)
                    ->setReasonForEmptySurveyOther(null);
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

    public function guardUnReject(GuardEvent $event)
    {
        $event->setBlocked(
            !$this->auditLogRepository->surveyHasPreviouslyBeenInClosedState(
                $this->getSurvey($event)
            )
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            // Transitions
            'workflow.domestic_survey.transition.invite_user' => 'transitionInviteUser',
            'workflow.domestic_survey.transition.complete' => 'transitionComplete',
            'workflow.domestic_survey.transition.reissue' => 'transitionReissue',
            'workflow.domestic_survey.transition.confirm_export' => 'transitionConfirmExport',
            'workflow.domestic_survey.transition.approve' => 'transitionApprove',
            'workflow.international_survey.transition.invite_user' => 'transitionInviteUser',
            'workflow.international_survey.transition.complete' => 'transitionComplete',
            'workflow.international_survey.transition.confirm_export' => 'transitionConfirmExport',
            'workflow.international_survey.transition.approve' => 'transitionApprove',
            'workflow.pre_enquiry.transition.invite_user' => 'transitionInviteUser',
            'workflow.pre_enquiry.transition.complete' => 'transitionComplete',

            // Guards
            'workflow.domestic_survey.guard.un_reject' => 'guardUnReject',
            'workflow.international_survey.guard.un_reject' => 'guardUnReject',
            'workflow.domestic_survey.guard.invite_user' => 'guardInviteUser',
            'workflow.international_survey.guard.invite_user' => 'guardInviteUser',
            'workflow.pre_enquiry.guard.invite_user' => 'guardInviteUser',
        ];
    }

    /**
     * @param SurveyInterface $survey
     */
    public function deletePasscodeUser(SurveyInterface $survey): void
    {
        $passcodeUser = $survey->getPasscodeUser();

        if ($passcodeUser) {
            $survey->setPasscodeUser(null);
            $this->entityManager->remove($passcodeUser);
        }
    }
}