<?php

namespace App\Tests\Utility\Rehearsal;

use App\Entity\NotifyApiResponse;
use App\Entity\RoRoUser;
use App\EventSubscriber\AlphagovNotifyMessengerSubscriber;
use App\Utility\Notification\NotificationType;
use App\Utility\Rehearsal\Scenario;
use App\Utility\Rehearsal\SeedManifest;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;

/**
 * Stands in for messenger:consume + Notify in kernel tests: attaches one response to every notification row the run
 * created on a seeded survey (id not in the manifest's history), and one per RoRo event to the seeded user. Invite rows
 * only with $includeInvites (= the consumer running after their 4-hour delay). Timezone-free: no timestamps compared.
 */
trait SimulatesNotifyConsumerTrait
{
    private function simulateConsumer(EntityManagerInterface $em, SeedManifest $manifest, bool $includeInvites = false, string $status = 'accepted'): void
    {
        $history = array_flip($manifest->historyRowIds);
        $response = fn(string $event) => (new NotifyApiResponse())
            ->setEventName($event)->setMessageClass(Email::class)->setEndpoint('/v2/notifications/email')
            ->setData([AlphagovNotifyMessengerSubscriber::STATUS_KEY => $status])->setTimestamp(new \DateTime());
        $user = $em->find(RoRoUser::class, $manifest->roroUserId);
        $roroEvents = [];

        foreach ($manifest->scenarios as $name => ['class' => $class, 'id' => $id]) {
            foreach ($em->find($class, $id)->getNotifications() as $row) {
                if (isset($history[(string) $row->getId()]) || (!$includeInvites && $row->getType() === NotificationType::INVITE)) {
                    continue;
                }
                if (str_starts_with($name, Scenario::RORO.':')) {
                    $roroEvents[$row->getType()->value] = true;
                    continue;
                }
                if ($row->getNotifyApiResponses()->isEmpty()) {
                    $r = $response($row->getType()->value);
                    $em->persist($r);
                    $row->addNotifyApiResponse($r);
                }
            }
        }
        foreach (array_keys($roroEvents) as $event) {
            $r = $response($event);
            $em->persist($r);
            $user->addNotifyApiResponse($r);
        }

        $em->flush();
    }
}
