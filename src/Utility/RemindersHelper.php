<?php


namespace App\Utility;


use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use App\Messenger\AlphagovNotify\Letter;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AlphagovNotify\TemplateReference;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
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
                get_class($survey),
                $survey->getId(),
                $address,
                $templateReference,
                PersonalisationHelper::getForEntity($survey),
            ));
            switch ($reminder) {
                case 1:
                    $survey->setFirstReminderSentDate(new \DateTime());
                    break;
                case 2:
                    $survey->setSecondReminderSentDate(new \DateTime());
                    break;
                default:
                    throw new \LogicException("unhandled reminder number: {$reminder}");
            }
        }
    }

    protected function getSurveysForReminding()
    {
        return [
            1 => [
                TemplateReference::LETTER_DOMESTIC_SURVEY_REMINDER_1 => $this->getSurveysForReminder1(DomesticSurvey::class),
                TemplateReference::LETTER_INTERNATIONAL_SURVEY_REMINDER_1 => $this->getSurveysForReminder1(InternationalSurvey::class),
            ],
            2 => [
                TemplateReference::LETTER_DOMESTIC_SURVEY_REMINDER_2 => $this->getSurveysForReminder2(DomesticSurvey::class),
                TemplateReference::LETTER_INTERNATIONAL_SURVEY_REMINDER_2 => $this->getSurveysForReminder2(InternationalSurvey::class),
            ],
        ];
    }

    protected function getSurveysForReminder1(string $surveyClass)
    {
        $qb = $this->getSurveyQueryBuilder($surveyClass);
        $qb->where($qb->expr()->andX(
            $this->getFirstReminderExpression($qb), // past survey period end and reminder not yet sent
            $this->getSurveyStateExpression($qb) // survey not submitted
        ));
        return $qb->getQuery()->execute();
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
            ->select('survey');
        return $queryBuilder->where($this->getSurveyStateExpression($queryBuilder));
    }

    protected function getSurveyStateExpression(QueryBuilder $queryBuilder)
    {
        // $queryBuilder->expr()->isNull('survey.submissionDate'),
        return $queryBuilder->expr()->in('survey.state', [SurveyInterface::STATE_NEW, SurveyInterface::STATE_INVITED_USER, SurveyInterface::STATE_IN_PROGRESS]);
    }

    protected function getFirstReminderExpression(QueryBuilder $queryBuilder)
    {
        $queryBuilder->setParameter('surveyEndDate', (new \DateTime())->sub(new \DateInterval('P7D')));
        return $queryBuilder->expr()->andX(
            $queryBuilder->expr()->isNull('survey.firstReminderSentDate'),
            $queryBuilder->expr()->lt('survey.surveyPeriodEnd', ':surveyEndDate')
        );
    }

    protected function getSecondReminderExpression(QueryBuilder $queryBuilder)
    {
        $queryBuilder->setParameter('firstReminderDate', (new \DateTime())->sub(new \DateInterval('P7D')));
        return $queryBuilder->expr()->andX(
            $queryBuilder->expr()->isNull('survey.secondReminderSentDate'),
            $queryBuilder->expr()->lt('survey.firstReminderSentDate', ':firstReminderDate')
        );
    }
}