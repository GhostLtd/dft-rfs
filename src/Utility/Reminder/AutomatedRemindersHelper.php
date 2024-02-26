<?php

namespace App\Utility\Reminder;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyReminderInterface;
use App\Entity\SurveyStateInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AlphagovNotify\Reference;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Provides methods to dispatch reminder notifications (both letter and email) for non-started surveys.
 */
class AutomatedRemindersHelper
{
    private const string TYPE_LETTER = 'letter';
    private const string TYPE_EMAIL = 'email';

    protected const LOG_PREFIX = 'Reminders: ';

    private const array REMINDER_TEMPLATE_MAP = [
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

    public function __construct(
        protected DisabledRemindersHelper $disabledRemindersHelper,
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface $logger,
        protected MessageBusInterface $messageBus,
        protected PersonalisationHelper $personalisationHelper,
    ) {}

    public function sendReminders(): void
    {
        // Send reminders for each that requires it
        foreach ($this->getSurveysForReminding() as $reminder => $surveyGroups) {
            foreach ($surveyGroups as $surveyClass => $surveys) {
                $templateReferences = self::REMINDER_TEMPLATE_MAP[$reminder][$surveyClass] ?? false;
                $this->logger->info(self::LOG_PREFIX."Starting batch; Reminder #{$reminder} for {$surveyClass}");
                foreach ($surveys as $survey) {
                    if ($templateReferences) {
                        $this->sendReminderMessages($reminder, $templateReferences, $survey);
                    }
                }
            }
        }

        // save the surveys with updated reminder dates
        $this->entityManager->flush();
    }

    protected function sendReminderMessages(int $reminder, array $templateReferences, SurveyReminderInterface $survey): void
    {
        $eventType = ([1 => Reference::EVENT_REMINDER_1, 2 => Reference::EVENT_REMINDER_2])[$reminder];

        $sentTypes = [];

        $address = $survey->getInvitationAddress();
        $addressIsFilled = $address && $address->isFilled();
        $letterTemplateReference = $templateReferences[self::TYPE_LETTER] ?? false;

        if ($addressIsFilled && $letterTemplateReference) {
            $this->messageBus->dispatch(new Letter(
                $eventType,
                $survey::class,
                $survey->getId(),
                $address,
                $letterTemplateReference,
                $this->personalisationHelper->getForEntity($survey),
            ));

            $sentTypes[] = 'letter';
        }

        $emailTemplateReference = $templateReferences[self::TYPE_EMAIL] ?? false;
        if ($emailTemplateReference)
        {
            $invitationEmails = $survey->getArrayOfInvitationEmails();

            foreach($invitationEmails as $invitationEmail) {
                $this->messageBus->dispatch(new Email(
                    $eventType,
                    $survey::class,
                    $survey->getId(),
                    $invitationEmail,
                    $emailTemplateReference,
                    $this->personalisationHelper->getForEntity($survey),
                ));
            }

            if (!empty($invitationEmails)) {
                $sentTypes[] = 'email';
            }
        }

        if (!empty($sentTypes)) {
            $sentTypes = join(', ', $sentTypes);
            $surveyId = $survey->getId();
            $this->logger->info(self::LOG_PREFIX."Sending {$sentTypes} for Survey {$surveyId}");
        }
    }

    protected function getSurveysForReminding(): array
    {
        $firstReminders = [];
        $secondReminders = [];

        $enabled = [];

        if (!$this->disabledRemindersHelper->isDisabled(DisabledRemindersHelper::CSRGT)) {
            $firstReminders[DomesticSurvey::class] = $this->getSurveysForReminder1(DomesticSurvey::class);
            $secondReminders[DomesticSurvey::class] = $this->getSurveysForReminder2(DomesticSurvey::class);
            $enabled[] = 'CSRGT';
        }

        if (!$this->disabledRemindersHelper->isDisabled(DisabledRemindersHelper::IRHS)) {
            $firstReminders[InternationalSurvey::class] = $this->getSurveysForReminder1(InternationalSurvey::class);
            $secondReminders[InternationalSurvey::class] = $this->getSurveysForReminder2(InternationalSurvey::class);
            $enabled[] = 'IRHS';
        }

        if (!$this->disabledRemindersHelper->isDisabled(DisabledRemindersHelper::PRE_ENQUIRY)) {
            $firstReminders[PreEnquiry::class] = $this->getPreEnquiriesForReminder1();
            $secondReminders[PreEnquiry::class] = $this->getSurveysForReminder2(PreEnquiry::class);
            $enabled[] = 'pre-enquiry';
        }

        if (empty($enabled)) {
            $this->logger->info(self::LOG_PREFIX."Reminder sending disabled for CSRGT, IRHS and Pre-enquiry. Exiting.");
        } else {
            $enabledFor = join(', ', $enabled);
            $this->logger->info(self::LOG_PREFIX."Reminder sending enabled for {$enabledFor}");
        }

        return [
            1 => $firstReminders,
            2 => $secondReminders,
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

    protected function getSurveyStateExpression(QueryBuilder $queryBuilder): Expr\Func
    {
        // Check that survey is not started
        return $queryBuilder->expr()->in('survey.state', [SurveyStateInterface::STATE_NEW, SurveyStateInterface::STATE_INVITATION_SENT]);
    }

    protected function getFirstReminderExpression(QueryBuilder $queryBuilder): Expr\Base
    {
        $queryBuilder->setParameter('sevenDaysAgo', (new DateTime())->modify('-7 days')->modify('+12 hours'));
        return $queryBuilder->expr()->andX(
            'survey.firstReminderSentDate IS NULL',
            'survey.secondReminderSentDate IS NULL',
            'survey.surveyPeriodEnd <= :sevenDaysAgo',
            'survey.invitationSentDate <= :sevenDaysAgo'
        );
    }

    protected function getPreEnquiryFirstReminderExpression(QueryBuilder $queryBuilder): Expr\Base
    {
        $queryBuilder->setParameter('fourteenDaysAgo', (new DateTime())->modify('-14 days')->modify('+12 hours'));
        return $queryBuilder->expr()->andX(
            'survey.firstReminderSentDate IS NULL',
            'survey.secondReminderSentDate IS NULL',
            'survey.invitationSentDate <= :fourteenDaysAgo'
        );
    }

    protected function getSecondReminderExpression(QueryBuilder $queryBuilder): Expr\Base
    {
        $queryBuilder->setParameter('fourteenDaysAgo', (new DateTime())->modify('-14 days')->modify('+12 hours'));
        return $queryBuilder->expr()->andX(
            'survey.secondReminderSentDate IS NULL',
            'survey.firstReminderSentDate <= :fourteenDaysAgo'
        );
    }
}