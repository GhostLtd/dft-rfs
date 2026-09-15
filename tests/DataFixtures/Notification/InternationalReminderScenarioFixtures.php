<?php

namespace App\Tests\DataFixtures\Notification;

use App\Entity\International\Company;
use App\Entity\International\Survey;
use App\Entity\International\SurveyNotification;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/** IRHS twin of ReminderScenarioFixtures: same "now" (2026-09-10 09:00), same reference names, same expected outcomes. */
class InternationalReminderScenarioFixtures extends Fixture
{
    public const string NOW = ReminderScenarioFixtures::NOW;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $make = function (string $ref, string $state, array $notifications, string $periodEnd = '2026-08-31') use ($manager): Survey {
            $company = (new Company())->setBusinessName("Company {$ref}");
            $survey = (new Survey())
                ->setSurveyPeriodStart(new \DateTime('2026-08-25'))
                ->setSurveyPeriodEnd(new \DateTime($periodEnd))
                ->setCompany($company)
                ->setReferenceNumber(substr(md5($ref), 0, 6).'-'.substr(md5($ref), 6, 4))
                ->setInvitationEmails('inv@example.com')
                ->setPasscodeUser((new PasscodeUser())->setUsername("int-{$ref}")->setPlainPassword('test'))
                ->setState($state);

            foreach ($notifications as [$type, $sentAt]) {
                $n = (new SurveyNotification())->setType($type)->setChannel(NotificationChannel::LETTER)->setSentAt(new \DateTime($sentAt));
                $survey->addNotification($n);
                $manager->persist($n);
            }

            $manager->persist($company);
            $manager->persist($survey);
            $this->addReference($ref, $survey);
            return $survey;
        };

        $make('r1:due', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-09-01 10:00']]);
        $make('r1:invite-too-recent', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-09-05 10:00']]);
        $make('r1:resent-invite-defers', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-20 10:00'], [NotificationType::INVITE, '2026-09-08 10:00']]);
        $make('r1:already-sent', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-20 10:00'], [NotificationType::REMINDER_1, '2026-08-28 10:00']]);
        $make('r1:blocked-by-r2', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-01 10:00'], [NotificationType::REMINDER_2, '2026-08-28 10:00']]);
        $make('r1:wrong-state', SurveyStateInterface::STATE_IN_PROGRESS, [[NotificationType::INVITE, '2026-09-01 10:00']]);
        $make('r1:tolerance', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-09-03 15:00']]); // 6d18h ago: within the 12h tolerance
        $make('r1:period-end-too-recent', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-20 10:00']], '2026-09-08');
        $make('r2:due', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-01 10:00'], [NotificationType::REMINDER_1, '2026-08-26 10:00']]);
        $make('r2:r1-too-recent', SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-01 10:00'], [NotificationType::REMINDER_1, '2026-09-01 10:00']]);

        $manager->flush();
    }
}
