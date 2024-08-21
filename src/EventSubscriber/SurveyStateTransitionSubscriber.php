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
    private const string TYPE_LETTER = 'letter';
    private const string TYPE_EMAIL = 'email';
    private const array TEMPLATE_MAP = [
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

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected MessageBusInterface    $messageBus,
        protected DomesticPdfHelper      $domesticPdfHelper,
        protected InternationalPdfHelper $internationalPdfHelper,
        protected AuditLogRepository     $auditLogRepository,
        protected PersonalisationHelper  $personalisationHelper
    ) {}

    public function guardInviteUser(GuardEvent $event): void
    {
        $survey = $this->getSurvey($event);
        $address = $survey->getInvitationAddress();
        if ((!$address || !$address->isFilled()) && !$survey->hasValidInvitationEmails()) {
            $event->setBlocked(true);
        }
    }

    public function transitionInviteUser(Event $event): void
    {
        $survey = $this->getSurvey($event);
        $personalisation = $this->personalisationHelper->getForEntity($survey);

        $address = $survey->getInvitationAddress();
        $addressIsFilled = $address && $address->isFilled();
        if ($addressIsFilled && self::TEMPLATE_MAP[$survey::class][self::TYPE_LETTER]) {
            $this->messageBus->dispatch(new Letter(
                Reference::EVENT_INVITE,
                $survey::class,
                $survey->getId(),
                $address,
                self::TEMPLATE_MAP[$survey::class][self::TYPE_LETTER],
                $personalisation,
            ));
        }

        if (self::TEMPLATE_MAP[$survey::class][self::TYPE_EMAIL]) {
            foreach ($survey->getArrayOfInvitationEmails() as $invitationEmail) {
                $this->messageBus->dispatch(new Email(
                    Reference::EVENT_INVITE,
                    $survey::class,
                    $survey->getId(),
                    $invitationEmail,
                    self::TEMPLATE_MAP[$survey::class][self::TYPE_EMAIL],
                    $personalisation,
                ));
            }
        }
    }

    public function surveyFinished(Event $event): void
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
            throw new \RuntimeException("Unsupported survey class" . $survey::class);
        }
    }

    public function transitionConfirmExport(Event $event): void
    {
        $survey = $this->getSurvey($event);
        $this->deletePasscodeUser($survey);
    }

    public function transitionReissue(Event $event): void
    {
        $survey = $this->getSurvey($event);
        $this->deletePasscodeUser($survey);
    }

    protected function getSurvey(Event $event): SurveyInterface
    {
        $survey = $event->getSubject();
        if (!$survey instanceof SurveyInterface) {
            throw new \LogicException("unhandled survey class: " . $survey::class);
        }
        return $survey;
    }

    public function guardUnReject(GuardEvent $event): void
    {
        $survey = $this->getSurvey($event);

        $hasPreviouslyBeenInClosedState = $this->auditLogRepository->surveyHasPreviouslyBeenInClosedState($survey);

        $isExemptVehicleType =
            $survey instanceof DomesticSurvey &&
            $survey->getResponse()?->getIsExemptVehicleType() === true;

        $event->setBlocked(!$hasPreviouslyBeenInClosedState && !$isExemptVehicleType);
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            // Transitions
            'workflow.domestic_survey.transition.invite_user' => 'transitionInviteUser',
            'workflow.domestic_survey.transition.reissue' => 'transitionReissue',
            'workflow.domestic_survey.transition.confirm_export' => 'transitionConfirmExport',
            'workflow.domestic_survey.entered.closed' => 'surveyFinished',
            'workflow.domestic_survey.entered.rejected' => 'surveyFinished',

            'workflow.international_survey.transition.invite_user' => 'transitionInviteUser',
            'workflow.international_survey.transition.confirm_export' => 'transitionConfirmExport',
            'workflow.international_survey.entered.closed' => 'surveyFinished',


            'workflow.pre_enquiry.transition.invite_user' => 'transitionInviteUser',
            'workflow.pre_enquiry.entered.closed' => 'surveyFinished',

            // Guards
            'workflow.domestic_survey.guard.un_reject' => 'guardUnReject',
            'workflow.international_survey.guard.un_reject' => 'guardUnReject',
            'workflow.domestic_survey.guard.invite_user' => 'guardInviteUser',
            'workflow.international_survey.guard.invite_user' => 'guardInviteUser',
            'workflow.pre_enquiry.guard.invite_user' => 'guardInviteUser',
        ];
    }

    public function deletePasscodeUser(SurveyInterface $survey): void
    {
        $passcodeUser = $survey->getPasscodeUser();

        if ($passcodeUser) {
            $survey->setPasscodeUser(null);
            $this->entityManager->remove($passcodeUser);
        }
    }
}
