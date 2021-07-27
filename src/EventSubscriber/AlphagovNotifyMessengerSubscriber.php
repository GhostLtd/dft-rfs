<?php


namespace App\EventSubscriber;


use Alphagov\Notifications\Exception\ApiException;
use App\Entity\SurveyInterface;
use App\Messenger\AlphagovNotify\AbstractMessage;
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
        if (!$originalMessage instanceof AbstractMessage) {
            return;
        }

        $notifyApiEvent = $event->getThrowable()->getPrevious();
        if ($notifyApiEvent instanceof UnrecoverableMessageHandlingException) {
            $notifyApiEvent = $notifyApiEvent->getPrevious();
        }

        if ($notifyApiEvent instanceof ApiException) {
            $notifyApiResponse = [
                'code' => $notifyApiEvent->getCode(),
                'errors' => $notifyApiEvent->getErrors(),
                self::STATUS_KEY => $event->willRetry()
                    ? Reference::STATUS_WAITING_FOR_RETRY
                    : Reference::STATUS_FAILED,
            ];
        } else {
            // We've got some other exception ?!
            $notifyApiResponse = [
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
        $this->logResponse($originalMessage, $notifyApiResponse);
    }

    public function onMessageHandled(WorkerMessageHandledEvent $event)
    {
        $originalMessage = $event->getEnvelope()->getMessage();
        if (!$originalMessage instanceof AbstractMessage) {
            return;
        }

        /** @var HandledStamp $stamp */
        $stamp = $event->getEnvelope()->last(HandledStamp::class);
        $notifyApiResponse = $stamp->getResult();
        $notifyApiResponse[self::STATUS_KEY] = Reference::STATUS_ACCEPTED;
        $this->logResponse($originalMessage, $notifyApiResponse);
    }

    protected function logResponse(AbstractMessage $message, array $notifyApiResponse)
    {
        $timestamp = new \DateTime();
        $notifyApiResponse['timestamp'] = $timestamp->format('c');

        /** @var ApiResponseInterface|SurveyInterface $entity */
        $entity = $this->entityManager->find($message->getOriginatingEntityClass(), $message->getOriginatingEntityId());

        if ($entity) {
            $workflow = $this->workflowRegistry->get($entity);
            $entity->setNotifyApiResponse($message->getEventName(), get_class($message), $notifyApiResponse);
            if ($notifyApiResponse[self::STATUS_KEY] === Reference::STATUS_ACCEPTED) {
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
            } else if ($notifyApiResponse[self::STATUS_KEY] === Reference::STATUS_FAILED) {
                if ($workflow->can($entity, 'invitation_failed')) {
                    $workflow->apply($entity, 'invitation_failed');
                }
            }
            $this->entityManager->flush();
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            WorkerMessageFailedEvent::class => ['onMessageFailed', -512],
            WorkerMessageHandledEvent::class => ['onMessageHandled', -512],
        ];
    }
}