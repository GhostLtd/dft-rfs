<?php

namespace App\EventSubscriber;

use Alphagov\Notifications\Exception\ApiException;
use App\Entity\NotifyApiResponse;
use App\Entity\SurveyReminderInterface;
use App\Entity\SurveyInterface;
use App\Messenger\AlphagovNotify\AbstractSendMessage;
use App\Messenger\AlphagovNotify\ApiResponseInterface;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Workflow\Registry;

class AlphagovNotifyMessengerSubscriber implements EventSubscriberInterface
{
    public const STATUS_KEY = 'symfony_messenger_bus.govuk_notify_status';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected Registry $workflowRegistry
    ) {}

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $originalMessage = $event->getEnvelope()->getMessage();
        if (!$originalMessage instanceof AbstractSendMessage) {
            return;
        }

        $notifyApiEvent = $event->getThrowable()->getPrevious();
        if ($notifyApiEvent instanceof UnrecoverableMessageHandlingException) {
            $notifyApiEvent = $notifyApiEvent->getPrevious();
        }

        if ($notifyApiEvent instanceof ApiException) {
            $apiResponseData = [
                'code' => $notifyApiEvent->getCode(),
                'errors' => $notifyApiEvent->getErrors(),
                self::STATUS_KEY => $event->willRetry()
                    ? Reference::STATUS_WAITING_FOR_RETRY
                    : Reference::STATUS_FAILED,
            ];
        } else {
            // We've got some other exception ?!
            $apiResponseData = [
                'code' => $notifyApiEvent->getCode(),
                'errors' => [
                    [
                        'error' => $notifyApiEvent->getMessage(),
                        'message' => $notifyApiEvent->getTraceAsString(),
                    ],
                ],
                self::STATUS_KEY => Reference::STATUS_FAILED,
            ];
        }

        $this->logResponse($originalMessage, $apiResponseData);
    }

    public function onMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $originalMessage = $event->getEnvelope()->getMessage();
        if (!$originalMessage instanceof AbstractSendMessage) {
            return;
        }

        /** @var HandledStamp $stamp */
        $stamp = $event->getEnvelope()->last(HandledStamp::class);
        $apiResponseData = $stamp->getResult();
        $apiResponseData[self::STATUS_KEY] = Reference::STATUS_ACCEPTED;
        $this->logResponse($originalMessage, $apiResponseData);
    }

    protected function logResponse(AbstractSendMessage $message, array $apiResponseData): void
    {
        /** @var ApiResponseInterface|SurveyInterface $entity */
        $entity = $this->entityManager->find($message->getOriginatingEntityClass(), $message->getOriginatingEntityId());

        if ($entity) {
           $timestamp = new \DateTime();

            $notifyApiResponse = (new NotifyApiResponse())
                ->setEndpoint($message->getEndpoint())
                ->setEventName($message->getEventName())
                ->setMessageClass($message::class)
                ->setData($apiResponseData)
                ->setRecipient($message->getRecipient())
                ->setRecipientHash($message->getRecipientHash())
                ->setTimestamp($timestamp);

            $this->entityManager->persist($notifyApiResponse);
            $entity->addNotifyApiResponse($notifyApiResponse);

            $status = $notifyApiResponse->getData()[self::STATUS_KEY];

            // We set the "sent date" immediately, otherwise upon the next cron run, the cron will see that no reminder
            // has been sent and will immediately queue another message.
            //
            // This would result in failed messages being perpetually re-tried, and the daily cron run would end up
            // hitting the notify API with an ever-increasing amount of calls.
            //
            // When the "sent date" is set immediately on the other hand, then failures will still be re-tried 3 times
            // by the message consumer using its exponential backoff settings. If it fails 3 times, then the error
            // will be permanent.

            // N.B. Everything below here only applies to CSRGT / IRHS / PreEnquiry.
            //      RoRo works a bit differently.

            if ($entity instanceof SurveyReminderInterface) {
                switch ($message->getEventName()) {
                    case Reference::EVENT_INVITE:
                        $entity->setInvitationSentDate($timestamp);
                        break;
                    case Reference::EVENT_REMINDER_1:
                        $entity->setFirstReminderSentDate($timestamp);
                        break;
                    case Reference::EVENT_REMINDER_2:
                        $entity->setSecondReminderSentDate($timestamp);
                        break;
                    case Reference::EVENT_MANUAL_REMINDER:
                        $entity->setLatestManualReminderSentDate($timestamp);
                        break;
                }
            }

            if ($this->workflowRegistry->has($entity)) {
                $workflow = $this->workflowRegistry->get($entity);

                if ($status === Reference::STATUS_ACCEPTED) {
                    if ($workflow->can($entity, 'invitation_sent')) {
                        $workflow->apply($entity, 'invitation_sent');
                    }
                } else if ($status === Reference::STATUS_FAILED) {
                    if ($workflow->can($entity, 'invitation_failed')) {
                        $workflow->apply($entity, 'invitation_failed');
                    }
                }
            }

            $this->entityManager->flush();
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => ['onMessageFailed', -512],
            WorkerMessageHandledEvent::class => ['onMessageHandled', -512],
        ];
    }
}