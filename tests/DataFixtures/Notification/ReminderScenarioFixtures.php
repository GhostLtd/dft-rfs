<?php

namespace App\Tests\DataFixtures\Notification;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * "Now" for these scenarios is 2026-09-10 09:00 (a Thursday). Survey periods ended 2026-08-31.
 * Reference names describe the expected outcome.
 */
class ReminderScenarioFixtures extends Fixture
{
    public const string NOW = '2026-09-10 09:00:00';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $make = function (string $ref, string $state, array $notifications, string $periodEnd = '2026-08-31') use ($manager): Survey {
            $survey = (new Survey())
                ->setSurveyPeriodStart(new \DateTime('2026-08-25'))
                ->setSurveyPeriodEnd(new \DateTime($periodEnd))
                ->setIsNorthernIreland(false)
                ->setRegistrationMark(strtoupper(substr(md5($ref), 0, 7)))
                ->setInvitationEmails('inv@example.com')
                ->setPasscodeUser((new PasscodeUser())->setUsername($ref)->setPlainPassword('test'))
                ->setState($state);

            foreach ($notifications as [$type, $sentAt]) {
                $n = (new SurveyNotification())->setType($type)->setChannel(NotificationChannel::LETTER)->setSentAt(new \DateTime($sentAt));
                $survey->addNotification($n);
                $manager->persist($n);
            }

            $manager->persist($survey);
            $this->addReference($ref, $survey);
            return $survey;
        };

        // reminder-1 candidates (invited >= 7 days ago and period ended >= 7 days ago)
        $make('r1:due', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-09-01 10:00']]);
        $make('r1:invite-too-recent', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-09-05 10:00']]);
        $make('r1:resent-invite-defers', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-20 10:00'], [NotificationType::INVITE, '2026-09-08 10:00']]);
        $make('r1:already-sent', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-20 10:00'], [NotificationType::REMINDER_1, '2026-08-28 10:00']]);
        $make('r1:blocked-by-r2', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-01 10:00'], [NotificationType::REMINDER_2, '2026-08-28 10:00']]);
        $make('r1:wrong-state', SurveyStateInterface::STATE_IN_PROGRESS, [[NotificationType::INVITE, '2026-09-01 10:00']]);
        $make('r1:tolerance', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-09-03 15:00']]); // 6d18h ago: within the 12h tolerance
        // invite is old enough, but the anchor (surveyPeriodEnd <= now - 7d + 12h = 2026-09-03 21:00) fails: period ends 2026-09-08
        $make('r1:period-end-too-recent', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-20 10:00']], '2026-09-08');

        // reminder-2 candidates
        $make('r2:due', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-01 10:00'], [NotificationType::REMINDER_1, '2026-08-26 10:00']]);
        $make('r2:r1-too-recent', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-01 10:00'], [NotificationType::REMINDER_1, '2026-09-01 10:00']]);

        $manager->flush();
    }
}
