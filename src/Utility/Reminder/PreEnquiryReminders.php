<?php

namespace App\Utility\Reminder;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryNotification;
use App\Utility\Notification\NotificationType;

/**
 * Pre-enquiry automated reminders. Two reminders, letter only (see NotificationTemplates), to pre-enquiries
 * that have not been started:
 *  - reminder-1: 14 days after the latest invitation (no survey-period condition: a pre-enquiry has no period; only
 *    while no reminder-2 has been sent)
 *  - reminder-2: 14 days after the latest reminder-1
 * "14 days" is applied with the existing 12-hour tolerance. Each pre-enquiry is sent in its own transaction.
 */
class PreEnquiryReminders extends AbstractSurveyReminders
{
    public function getFamily(): string
    {
        return DisabledRemindersHelper::PRE_ENQUIRY;
    }

    /** @return list<PreEnquiry> */
    public function dueForReminder1(\DateTimeImmutable $now): array
    {
        $cutoff = $this->cutoff($now, 'P14D');

        $qb = $this->entityManager->createQueryBuilder()->select('p')->from(PreEnquiry::class, 'p')
            ->where('p.state IN (:notStarted)')->setParameter('notStarted', self::NOT_STARTED);
        $this->rows->none($qb, 'p', PreEnquiryNotification::class, NotificationType::REMINDER_1);
        $this->rows->none($qb, 'p', PreEnquiryNotification::class, NotificationType::REMINDER_2);
        $this->rows->latestAtOrBefore($qb, 'p', PreEnquiryNotification::class, NotificationType::INVITE, $cutoff);

        return $qb->getQuery()->getResult();
    }

    /** @return list<PreEnquiry> */
    public function dueForReminder2(\DateTimeImmutable $now): array
    {
        $cutoff = $this->cutoff($now, 'P14D');

        $qb = $this->entityManager->createQueryBuilder()->select('p')->from(PreEnquiry::class, 'p')
            ->where('p.state IN (:notStarted)')->setParameter('notStarted', self::NOT_STARTED);
        $this->rows->none($qb, 'p', PreEnquiryNotification::class, NotificationType::REMINDER_2);
        $this->rows->latestAtOrBefore($qb, 'p', PreEnquiryNotification::class, NotificationType::REMINDER_1, $cutoff);

        return $qb->getQuery()->getResult();
    }
}
