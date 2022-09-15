<?php

namespace App\EventSubscriber;

use Alphagov\Notifications\Exception\ApiException;
use App\Entity\NotifyApiResponse;
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
    const STATUS_KEY = 'symfony_messenger_bus.govuk_notify_status';

    private EntityManagerInterface $entityManager;
    private Registry $workflowRegistry;

    public function __construct(EntityManagerInterface $entityManager, Registry $workflowRegistry)
    {
        $this->entityManager = $entityManager;
        $this->workflowRegistry = $workflowRegistry;
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event)
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

    public function onMessageHandled(WorkerMessageHandledEvent $event)
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

    protected function logResponse(AbstractSendMessage $message, array $apiResponseData)
    {
        /** @var ApiResponseInterface|SurveyInterface $entity */
        $entity = $this->entityManager->find($message->getOriginatingEntityClass(), $message->getOriginatingEntityId());

        if ($entity) {
            $workflow = $this->workflowRegistry->get($entity);

            $timestamp = new \DateTime();

            $notifyApiResponse = (new NotifyApiResponse())
                ->setEndpoint($message->getEndpoint())
                ->setEventName($message->getEventName())
                ->setMessageClass(get_class($message))
                ->setData($apiResponseData)
                ->setRecipient($message->getRecipient())
                ->setRecipientHash($message->getRecipientHash())
                ->setTimestamp($timestamp);

            $this->entityManager->persist($notifyApiResponse);
            $entity->addNotifyApiResponse($notifyApiResponse);

            $status = $notifyApiResponse->getData()[self::STATUS_KEY];

            if ($status === Reference::STATUS_ACCEPTED) {
                if ($workflow->can($entity, 'invitation_sent')) {
                    $workflow->apply($entity, 'invitation_sent');
                }
                switch($message->getEventName()) {
                    case Reference::EVENT_INVITE :
                        $entity->setNotifiedDate($timestamp);
                        break;
                    case Reference::EVENT_REMINDER_1 :
                        $entity->setFirstReminderSentDate($timestamp);
                        break;
                    case Reference::EVENT_REMINDER_2 :
                        $entity->setSecondReminderSentDate($timestamp);
                        break;
                }
            } else if ($status === Reference::STATUS_FAILED) {
                if ($workflow->can($entity, 'invitation_failed')) {
                    $workflow->apply($entity, 'invitation_failed');
                }
            }

            $this->entityManager->flush();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => ['onMessageFailed', -512],
            WorkerMessageHandledEvent::class => ['onMessageHandled', -512],
        ];
    }
}