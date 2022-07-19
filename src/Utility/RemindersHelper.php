<?php

namespace App\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AlphagovNotify\Reference;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Messenger\MessageBusInterface;

class RemindersHelper
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;
    private PersonalisationHelper $personalisationHelper;

    private const TYPE_LETTER = 'letter';
    private const TYPE_EMAIL = 'email';
    private const REMINDER_TEMPLATE_MAP = [
        1 => [
            DomesticSurvey::class => [
                self::TYPE_LETTER => Reference::LETTER_DOMESTIC_SURVEY_REMINDER_1,
                self::TYPE_EMAIL => Reference::EMAIL_DOMESTIC_SURVEY_REMINDER_1
            ],
            InternationalSurvey::class => [
                self::TYPE_LETTER => Reference::LETTER_INTERNATIONAL_SURVEY_REMINDER_1,
                self::TYPE_EMAIL => Reference::EMAIL_INTERNATIONAL_SURVEY_REMINDER_1,
            ],
            PreEnquiry::class => [
                self::TYPE_LETTER => Reference::LETTER_PRE_ENQUIRY_REMINDER_1,
                self::TYPE_EMAIL => Reference::EMAIL_PRE_ENQUIRY_REMINDER_1,
            ],
        ],
        2 => [
            DomesticSurvey::class => [
                self::TYPE_LETTER => Reference::LETTER_DOMESTIC_SURVEY_REMINDER_2,
                self::TYPE_EMAIL => Reference::EMAIL_DOMESTIC_SURVEY_REMINDER_2
            ],
            InternationalSurvey::class => [
                self::TYPE_LETTER => Reference::LETTER_INTERNATIONAL_SURVEY_REMINDER_2,
                self::TYPE_EMAIL => Reference::EMAIL_INTERNATIONAL_SURVEY_REMINDER_2,
            ],
            PreEnquiry::class => [
                self::TYPE_LETTER => Reference::LETTER_PRE_ENQUIRY_REMINDER_2,
                self::TYPE_EMAIL => Reference::EMAIL_PRE_ENQUIRY_REMINDER_2,
            ],
        ],
    ];

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $messageBus, PersonalisationHelper $personalisationHelper)
    {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->personalisationHelper = $personalisationHelper;
    }

    public function sendReminders()
    {
        // Send reminders for each that requires it
        foreach ($this->getSurveysForReminding() as $reminder => $surveyGroups)
            foreach ($surveyGroups as $surveyClass => $surveys) {
                $templateReferences = self::REMINDER_TEMPLATE_MAP[$reminder][$surveyClass] ?? false;
                foreach ($surveys as $survey) {
                    if ($templateReferences) $this->sendReminderMessages($reminder, $templateReferences, $survey);
                }
            }

        // save the surveys with updated reminder dates
        $this->entityManager->flush();
    }

    protected function sendReminderMessages(int $reminder, array $templateReferences, SurveyInterface $survey)
    {
        $eventType = ([1 => Reference::EVENT_REMINDER_1, 2 => Reference::EVENT_REMINDER_2])[$reminder];

        $address = $survey->getInvitationAddress();
        $addressIsFilled = $address && $address->isFilled();
        $letterTemplateReference = $templateReferences[self::TYPE_LETTER] ?? false;
        if ($addressIsFilled && $letterTemplateReference) {
            $this->messageBus->dispatch(new Letter(
                $eventType,
                get_class($survey),
                $survey->getId(),
                $address,
                $letterTemplateReference,
                $this->personalisationHelper->getForEntity($survey),
            ));
        }

        $emailTemplateReference = $templateReferences[self::TYPE_EMAIL] ?? false;
        if ($survey->getInvitationEmails() && $emailTemplateReference)
        {
            $invitationEmails = array_map('trim', explode(',', $survey->getInvitationEmails()));
            foreach($invitationEmails as $invitationEmail) {
                $this->messageBus->dispatch(new Email(
                    $eventType,
                    get_class($survey),
                    $survey->getId(),
                    $invitationEmail,
                    $emailTemplateReference,
                    $this->personalisationHelper->getForEntity($survey),
                ));
            }
        }

    }

    protected function getSurveysForReminding()
    {
        return [
            1 => [
                DomesticSurvey::class => $this->getSurveysForReminder1(DomesticSurvey::class),
                InternationalSurvey::class => $this->getSurveysForReminder1(InternationalSurvey::class),
                PreEnquiry::class => $this->getPreEnquiriesForReminder1(),
            ],
            2 => [
                DomesticSurvey::class => $this->getSurveysForReminder2(DomesticSurvey::class),
                InternationalSurvey::class => $this->getSurveysForReminder2(InternationalSurvey::class),
                PreEnquiry::class => $this->getSurveysForReminder2(PreEnquiry::class),
            ],
        ];
    }

    protected function getSurveysForReminder1(string $surveyClass)
    {
        $qb = $this->getSurveyQueryBuilder($surveyClass);
        return $qb
            ->andWhere($this->getFirstReminderExpression($qb))
            ->getQuery()
            ->execute();
    }

    protected function getPreEnquiriesForReminder1()
    {
        $qb = $this->getSurveyQueryBuilder(PreEnquiry::class);
        return $qb
            ->andWhere($this->getPreEnquiryFirstReminderExpression($qb))
            ->getQuery()
            ->execute();
    }

    protected function getSurveysForReminder2(string $surveyClass)
    {
        $qb = $this->getSurveyQueryBuilder($surveyClass);
        return $qb
            ->andWhere($this->getSecondReminderExpression($qb))
            ->getQuery()
            ->execute();
    }

    protected function getSurveyQueryBuilder(string $surveyClass): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from($surveyClass, 'survey')
            ->leftJoin('survey.response', 'response')
            ->select('survey');
        return $queryBuilder->andWhere($this->getSurveyStateExpression($queryBuilder));
    }

    protected function getSurveyStateExpression(QueryBuilder $queryBuilder): Expr\Base
    {
        // $queryBuilder->expr()->isNull('survey.submissionDate'),
        return $queryBuilder->expr()->andX(
            $queryBuilder->expr()->in('survey.state', [SurveyInterface::STATE_NEW, SurveyInterface::STATE_INVITATION_SENT, SurveyInterface::STATE_IN_PROGRESS]),
            $queryBuilder->expr()->andX('response.contactTelephone IS NULL', 'response.contactEmail IS NULL')
        );
    }

    protected function getFirstReminderExpression(QueryBuilder $queryBuilder): Expr\Base
    {
        $queryBuilder->setParameter('sevenDaysAgo', (new DateTime())->modify('-7 days')->modify('+12 hours'));
        return $queryBuilder->expr()->andX(
            'survey.firstReminderSentDate IS NULL',
            'survey.secondReminderSentDate IS NULL',
            'survey.surveyPeriodEnd <= :sevenDaysAgo',
            'survey.notifiedDate <= :sevenDaysAgo'
        );
    }

    protected function getPreEnquiryFirstReminderExpression(QueryBuilder $queryBuilder): Expr\Base
    {
        $queryBuilder->setParameter('fourteenDaysAgo', (new DateTime())->modify('-14 days')->modify('+12 hours'));
        return $queryBuilder->expr()->andX(
            'survey.firstReminderSentDate IS NULL',
            'survey.secondReminderSentDate IS NULL',
            'survey.notifiedDate <= :fourteenDaysAgo'
        );
    }

    protected function getSecondReminderExpression(QueryBuilder $queryBuilder): Expr\Base
    {
        $queryBuilder->setParameter('firstReminderDate', (new DateTime())->modify('-14 days')->modify('+12 hours'));
        return $queryBuilder->expr()->andX(
            'survey.secondReminderSentDate IS NULL',
            'survey.firstReminderSentDate <= :firstReminderDate'
        );
    }
}