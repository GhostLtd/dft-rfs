<?php

namespace App\Tests\DataFixtures\Notification;

use App\Entity\Embeddable\LongAddress;
use App\Entity\PasscodeUser;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryNotification;
use App\Entity\SurveyStateInterface;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Pre-enquiry reminder scenarios. "Now" is 2026-09-10 09:00. Unlike CSRGT/IRHS, reminder-1 waits 14 days
 * (cutoff 2026-08-27 21:00 with the 12h tolerance) and has no survey-period-end condition; sends are letter-only.
 */
class PreEnquiryReminderScenarioFixtures extends Fixture
{
    public const string NOW = ReminderScenarioFixtures::NOW;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // PreEnquiryStateLifecycleSubscriber only fires on INSERT: prePersist forces state to STATE_NEW,
        // and postPersist auto-applies "invite_user" (dispatching a real, now-dated invite) once the entity
        // has an address or emails. So each pre-enquiry is inserted bare first (no state, no address, no
        // emails -- the guard blocks the auto-transition) and the scenario's state/address/notifications are
        // added in a second, UPDATE-only flush, which the subscriber never sees.
        $scenarios = [
            // reminder-1 candidates (latest invite >= 14 days ago, minus the 12h tolerance)
            'r1:due' => [SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-20 10:00']]],
            'r1:invite-too-recent' => [SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-09-01 10:00']]], // 9 days: due for CSRGT, not for PRE
            'r1:resent-invite-defers' => [SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-01 10:00'], [NotificationType::INVITE, '2026-09-05 10:00']]],
            // reminder-1 on 1 Sept: excluded from reminder-1 (already sent) AND too recent for reminder-2 (cutoff 27 Aug 21:00)
            'r1:already-sent' => [SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-01 10:00'], [NotificationType::REMINDER_1, '2026-09-01 10:00']]],
            'r1:blocked-by-r2' => [SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-07-01 10:00'], [NotificationType::REMINDER_2, '2026-08-20 10:00']]],
            'r1:wrong-state' => [SurveyStateInterface::STATE_IN_PROGRESS, [[NotificationType::INVITE, '2026-08-20 10:00']]],
            'r1:tolerance' => [SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-27 15:00']]], // 13d18h ago: within the 12h tolerance

            // reminder-2 candidates (latest reminder-1 >= 14 days ago)
            'r2:due' => [SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-01 10:00'], [NotificationType::REMINDER_1, '2026-08-26 10:00']]],
            'r2:r1-too-recent' => [SurveyStateInterface::STATE_INVITATION_SENT, [[NotificationType::INVITE, '2026-08-01 10:00'], [NotificationType::REMINDER_1, '2026-09-01 10:00']]],
        ];

        // Phase 1 (insert): bare entities only -- no state, no invitation address, no invitation emails --
        // so PreEnquiryStateLifecycleSubscriber's guard blocks "invite_user" and state stays "new".
        $preEnquiries = [];
        foreach (array_keys($scenarios) as $ref) {
            $preEnquiries[$ref] = (new PreEnquiry())
                ->setReferenceNumber(substr(md5($ref), 0, 8))
                ->setCompanyName("Company {$ref}")
                ->setDispatchDate(new \DateTime('2026-08-01'))
                ->setPasscodeUser((new PasscodeUser())->setUsername("pre-{$ref}")->setPlainPassword('test'));
            $manager->persist($preEnquiries[$ref]);
        }
        $manager->flush();

        // Phase 2 (update): fill in the scenario's real state, address, and notification rows. Updates
        // don't trigger prePersist/postPersist, so these values are the ones the tests see.
        foreach ($scenarios as $ref => [$state, $notifications]) {
            $preEnquiry = $preEnquiries[$ref]
                ->setState($state)
                ->setInvitationAddress((new LongAddress())->setLine1("Company {$ref}")->setLine2('1 Road')->setPostcode('BA1 1HR'));

            foreach ($notifications as [$type, $sentAt]) {
                $n = (new PreEnquiryNotification())->setType($type)->setChannel(NotificationChannel::LETTER)->setSentAt(new \DateTime($sentAt));
                $preEnquiry->addNotification($n);
                $manager->persist($n);
            }

            $this->addReference($ref, $preEnquiry);
        }
        $manager->flush();
    }
}
