<?php

namespace App\Utility\Reminder;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Utility\Notification\NotificationType;

/**
 * CSRGT automated reminders. Two reminders, letter and email, to surveys that have not been started:
 *  - reminder-1: 7 days after the latest invitation, once the survey period has ended for 7 days (and only
 *    while no reminder-2 has been sent)
 *  - reminder-2: 14 days after the latest reminder-1
 * "N days" is applied with the existing 12-hour tolerance. Each survey is sent in its own transaction.
 */
class DomesticReminders extends AbstractSurveyReminders
{
    public function getFamily(): string
    {
        return DisabledRemindersHelper::CSRGT;
    }

    /** @return list<Survey> */
    public function dueForReminder1(\DateTimeImmutable $now): array
    {
        $cutoff = $this->cutoff($now, 'P7D');

        $qb = $this->entityManager->createQueryBuilder()->select('s')->from(Survey::class, 's')
            ->where('s.state IN (:notStarted)')->setParameter('notStarted', self::NOT_STARTED)
            ->andWhere('s.surveyPeriodEnd <= :periodEndCutoff')->setParameter('periodEndCutoff', \DateTime::createFromImmutable($cutoff));
        $this->rows->none($qb, 's', SurveyNotification::class, NotificationType::REMINDER_1);
        $this->rows->none($qb, 's', SurveyNotification::class, NotificationType::REMINDER_2);
        $this->rows->latestAtOrBefore($qb, 's', SurveyNotification::class, NotificationType::INVITE, $cutoff);

        return $qb->getQuery()->getResult();
    }

    /** @return list<Survey> */
    public function dueForReminder2(\DateTimeImmutable $now): array
    {
        $cutoff = $this->cutoff($now, 'P14D');

        $qb = $this->entityManager->createQueryBuilder()->select('s')->from(Survey::class, 's')
            ->where('s.state IN (:notStarted)')->setParameter('notStarted', self::NOT_STARTED);
        $this->rows->none($qb, 's', SurveyNotification::class, NotificationType::REMINDER_2);
        $this->rows->latestAtOrBefore($qb, 's', SurveyNotification::class, NotificationType::REMINDER_1, $cutoff);

        return $qb->getQuery()->getResult();
    }
}
