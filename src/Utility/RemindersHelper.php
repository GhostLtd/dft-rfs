<?php

namespace App\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use App\Messenger\AlphagovNotify\Letter;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Messenger\MessageBusInterface;

class RemindersHelper
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $messageBus)
    {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
    }

    public function sendReminders()
    {
        // Send reminders for each that requires it
        foreach ($this->getSurveysForReminding() as $reminder => $surveyGroups)
            foreach ($surveyGroups as $templateReference => $surveys)
                foreach ($surveys as $survey)
                    $this->sendReminder($reminder, $templateReference, $survey);

        // save the surveys with updated reminder dates
        $this->entityManager->flush();
    }

    protected function sendReminder(int $reminder, string $templateReference, SurveyInterface $survey)
    {
        $address = $survey->getInvitationAddress();
        $addressIsFilled = $address && $address->isFilled();
        if ($addressIsFilled) {
            $this->messageBus->dispatch(new Letter(
                ([1 => Reference::EVENT_REMINDER_1, 2 => Reference::EVENT_REMINDER_2])[$reminder],
                get_class($survey),
                $survey->getId(),
                $address,
                $templateReference,
                PersonalisationHelper::getForEntity($survey),
            ));
        }
    }

    protected function getSurveysForReminding()
    {
        // TODO: Pre-enquiry reminders. Although I've modified preEnquiry responses to be in line with surveyResponses
        //       in terms of field naming (e.g. contactName, contactTelephone, contactEmail), a preEnquiry does not have
        //       a due date, which is required for the getFirt/SecondReminderExpression methods
        return [
            1 => [
                Reference::LETTER_DOMESTIC_SURVEY_REMINDER_1 => $this->getSurveysForReminder1(DomesticSurvey::class),
                Reference::LETTER_INTERNATIONAL_SURVEY_REMINDER_1 => $this->getSurveysForReminder1(InternationalSurvey::class),
            ],
            2 => [
                Reference::LETTER_DOMESTIC_SURVEY_REMINDER_2 => $this->getSurveysForReminder2(DomesticSurvey::class),
                Reference::LETTER_INTERNATIONAL_SURVEY_REMINDER_2 => $this->getSurveysForReminder2(InternationalSurvey::class),
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

    protected function getSurveysForReminder2(string $surveyClass)
    {
        $qb = $this->getSurveyQueryBuilder($surveyClass);
        return $qb
            ->andWhere($this->getSecondReminderExpression($qb))
            ->getQuery()
            ->execute();
    }

    protected function getSurveyQueryBuilder(string $surveyClass)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from($surveyClass, 'survey')
            ->leftJoin('survey.response', 'response')
            ->select('survey');
        return $queryBuilder->andWhere($this->getSurveyStateExpression($queryBuilder));
    }

    protected function getSurveyStateExpression(QueryBuilder $queryBuilder)
    {
        // $queryBuilder->expr()->isNull('survey.submissionDate'),
        return $queryBuilder->expr()->andX(
            $queryBuilder->expr()->in('survey.state', [SurveyInterface::STATE_NEW, SurveyInterface::STATE_INVITATION_SENT, SurveyInterface::STATE_IN_PROGRESS]),
            $queryBuilder->expr()->andX('response.contactTelephone IS NULL', 'response.contactEmail IS NULL')
        );
    }

    protected function getFirstReminderExpression(QueryBuilder $queryBuilder)
    {
        $queryBuilder->setParameter('surveyEndDate', (new \DateTime())->modify('-7 days')->modify('+12 hours'));
        return $queryBuilder->expr()->andX(
            'survey.firstReminderSentDate IS NULL',
            'survey.secondReminderSentDate IS NULL',
            'survey.surveyPeriodEnd <= :surveyEndDate'
        );
    }

    protected function getSecondReminderExpression(QueryBuilder $queryBuilder)
    {
        $queryBuilder->setParameter('firstReminderDate', (new \DateTime())->modify('-14 days')->modify('+12 hours'));
        return $queryBuilder->expr()->andX(
            'survey.secondReminderSentDate IS NULL',
            'survey.firstReminderSentDate <= :firstReminderDate'
        );
    }
}