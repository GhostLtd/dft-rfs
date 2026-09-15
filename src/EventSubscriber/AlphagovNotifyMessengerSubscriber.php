<?php

namespace App\EventSubscriber;

use Alphagov\Notifications\Exception\ApiException;
use App\Entity\NotifyApiResponse;
use App\Entity\SurveyNotificationInterface;
use App\Messenger\AlphagovNotify\ApiResponseInterface;
use App\Utility\AlphagovNotify\Reference;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\AbstractMessage;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Workflow\Registry;

class AlphagovNotifyMessengerSubscriber implements EventSubscriberInterface
{
    public const string STATUS_KEY = 'symfony_messenger_bus.govuk_notify_status';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected Registry $workflowRegistry,
    ) {}

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $originalMessage = $event->getEnvelope()->getMessage();
        if (!$originalMessage instanceof AbstractMessage) {
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
        if (!$originalMessage instanceof AbstractMessage) {
            return;
        }

        /** @var HandledStamp $stamp */
        $stamp = $event->getEnvelope()->last(HandledStamp::class);
        $apiResponseData = $stamp->getResult();
        $apiResponseData[self::STATUS_KEY] = Reference::STATUS_ACCEPTED;
        $this->logResponse($originalMessage, $apiResponseData);
    }

    protected function logResponse(AbstractMessage $message, array $apiResponseData): void
    {
        $entity = $this->entityManager->find($message->getOriginatingEntityClass(), $message->getOriginatingEntityId());
        if (!$entity) {
            return;
        }

        $notifyApiResponse = (new NotifyApiResponse())
            ->setEndpoint(match ($message::class) {
                Email::class => '/v2/notifications/email',
                Letter::class => '/v2/notifications/letter',
                Sms::class => '/v2/notifications/sms',
                default => throw new \InvalidArgumentException(sprintf('Unexpected message class "%s"', $message::class)),
            })
            ->setEventName($message->getEventName())
            ->setMessageClass($message::class)
            ->setData($apiResponseData)
            ->setRecipient($message->getRecipient())
            ->setRecipientHash($message->getRecipientHash())
            ->setTimestamp(new \DateTime());
        $this->entityManager->persist($notifyApiResponse);

        $survey = null;

        if ($entity instanceof SurveyNotificationInterface) {
            $survey = $entity->getSurvey();
        }

        if ($entity instanceof ApiResponseInterface) {
            $entity->addNotifyApiResponse($notifyApiResponse);
        }

        $status = $apiResponseData[self::STATUS_KEY];
        $workflowSubject = $survey ?? $entity;

        if ($this->workflowRegistry->has($workflowSubject)) {
            $workflow = $this->workflowRegistry->get($workflowSubject);
            if ($status === Reference::STATUS_ACCEPTED) {
                if ($workflow->can($workflowSubject, 'invitation_sent')) {
                    $workflow->apply($workflowSubject, 'invitation_sent');
                }
            } else if ($status === Reference::STATUS_FAILED) {
                if ($workflow->can($workflowSubject, 'invitation_failed')) {
                    $workflow->apply($workflowSubject, 'invitation_failed');
                }
            }
        }

        $this->entityManager->flush();
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