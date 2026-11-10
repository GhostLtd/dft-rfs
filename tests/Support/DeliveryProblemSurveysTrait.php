<?php

namespace App\Tests\Support;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\NotifyApiResponse;
use App\Entity\NotifyCallback;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;
use App\EventSubscriber\AlphagovNotifyMessengerSubscriber;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;

/**
 * CSRGT surveys covering each kind of delivery outcome, keyed by label:
 *
 * - SENDFAIL1 / SENDFAIL2: send-time failure, unseen / seen
 * - AFTER1 / AFTER2: permanent failure (unseen) / returned letter (seen)
 * - TEMP: temporary failure only (not a problem)
 * - OK: delivered
 * - NONE: no notifications
 * - MIXED: a seen send-time failure and an unseen permanent failure
 */
trait DeliveryProblemSurveysTrait
{
    /** Label => a valid registration mark (list pages show invalid ones blank) */
    private const array DELIVERY_PROBLEM_REG_MARKS = [
        'SENDFAIL1' => 'SF01AAA',
        'SENDFAIL2' => 'SF02AAA',
        'AFTER1' => 'AF01AAA',
        'AFTER2' => 'AF02AAA',
        'TEMP' => 'TE01AAA',
        'OK' => 'OK01AAA',
        'NONE' => 'NO01AAA',
        'MIXED' => 'MX01AAA',
    ];

    private function deliveryProblemLabel(string $registrationMark): string
    {
        return array_search(str_replace(' ', '', $registrationMark), self::DELIVERY_PROBLEM_REG_MARKS, true);
    }

    /** @return array<string, Survey> keyed by label */
    private function createDeliveryProblemSurveys(EntityManagerInterface $entityManager): array
    {
        $seen = new \DateTime('2026-11-01 12:00');
        /** @var array<string, list<array{string, ?string, ?\DateTime}>> $specs response status, callback status, seen at */
        $specs = [
            'SENDFAIL1' => [[Reference::STATUS_FAILED, null, null]],
            'SENDFAIL2' => [[Reference::STATUS_FAILED, null, $seen]],
            'AFTER1' => [[Reference::STATUS_ACCEPTED, NotifyCallback::STATUS_PERMANENT_FAILURE, null]],
            'AFTER2' => [[Reference::STATUS_ACCEPTED, NotifyCallback::STATUS_RETURNED, $seen]],
            'TEMP' => [[Reference::STATUS_ACCEPTED, NotifyCallback::STATUS_TEMPORARY_FAILURE, null]],
            'OK' => [[Reference::STATUS_ACCEPTED, NotifyCallback::STATUS_DELIVERED, null]],
            'NONE' => [],
            'MIXED' => [
                [Reference::STATUS_FAILED, null, $seen],
                [Reference::STATUS_ACCEPTED, NotifyCallback::STATUS_PERMANENT_FAILURE, null],
            ],
        ];

        $surveys = [];
        $n = 0;
        foreach ($specs as $label => $responses) {
            $regMark = self::DELIVERY_PROBLEM_REG_MARKS[$label];
            $survey = (new Survey())
                ->setSurveyPeriodStart(new \DateTime('2026-08-01'))->setSurveyPeriodEnd(new \DateTime('2026-08-07'))
                ->setIsNorthernIreland(false)->setRegistrationMark($regMark)->setQualityAssured(false)
                ->setPasscodeUser((new PasscodeUser())->setUsername("dp-{$regMark}")->setPlainPassword('x'))
                ->setState(SurveyStateInterface::STATE_INVITATION_SENT);
            $entityManager->persist($survey);

            foreach ($responses as [$status, $callbackStatus, $seenAt]) {
                $n++;
                $notifyId = sprintf('00000000-0000-4000-8000-%012d', $n);
                $row = (new SurveyNotification())->setType(NotificationType::INVITE)->setChannel(NotificationChannel::EMAIL)->setSentAt(new \DateTime('2026-08-01'));
                $survey->addNotification($row);
                $response = (new NotifyApiResponse())
                    ->setEventName('invite')->setMessageClass(Email::class)->setEndpoint('/v2/notifications/email')
                    // As the subscriber records them: failures always carry their errors
                    ->setData([AlphagovNotifyMessengerSubscriber::STATUS_KEY => $status] + ($status === Reference::STATUS_ACCEPTED ? [] : ['errors' => []]))
                    ->setNotifyId($status === Reference::STATUS_ACCEPTED ? $notifyId : null)
                    ->setProblemSeenAt($seenAt)->setTimestamp(new \DateTime('2026-08-01'));
                $row->addNotifyApiResponse($response);
                $entityManager->persist($row);
                $entityManager->persist($response);

                if ($callbackStatus !== null) {
                    $entityManager->persist((new NotifyCallback())
                        ->setResponse($response)->setNotifyId($notifyId)
                        ->setType($callbackStatus === NotifyCallback::STATUS_RETURNED ? NotifyCallback::TYPE_RETURNED_LETTER : NotifyCallback::TYPE_DELIVERY_RECEIPT)
                        ->setStatus($callbackStatus)->setReceivedAt(new \DateTime('2026-08-02')));
                }
            }

            $surveys[$label] = $survey;
        }

        $entityManager->flush();
        $entityManager->clear();

        return $surveys;
    }
}
