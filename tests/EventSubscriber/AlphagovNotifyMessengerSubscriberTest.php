<?php

namespace App\Tests\EventSubscriber;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;
use App\EventSubscriber\AlphagovNotifyMessengerSubscriber;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class AlphagovNotifyMessengerSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AlphagovNotifyMessengerSubscriber $subscriber;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([]);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->subscriber = $container->get(AlphagovNotifyMessengerSubscriber::class);
    }

    public function testRetriedNonApiFailureIsWaitingForRetryAndDoesNotFailTheInvitation(): void
    {
        $row = $this->pendingInvitation();
        $envelope = $this->envelope($row);

        $this->subscriber->onMessageFailed($this->failedEvent($envelope, new \RuntimeException('Connection timed out'), willRetry: true));

        $this->entityManager->clear();
        $row = $this->entityManager->find(SurveyNotification::class, $row->getId());
        self::assertSame([Reference::STATUS_WAITING_FOR_RETRY], $this->statuses($row));
        self::assertSame(SurveyStateInterface::STATE_INVITATION_PENDING, $this->surveyState($row));
    }

    public function testRetriedNonApiFailureFollowedBySuccessEndsInvitationSent(): void
    {
        $row = $this->pendingInvitation();
        $envelope = $this->envelope($row);

        $this->subscriber->onMessageFailed($this->failedEvent($envelope, new \RuntimeException('Connection timed out'), willRetry: true));
        $this->subscriber->onMessageHandled(new WorkerMessageHandledEvent(
            $envelope->with(new HandledStamp(['id' => 'notify-id'], 'handler')),
            'async_notify',
        ));

        $this->entityManager->clear();
        $row = $this->entityManager->find(SurveyNotification::class, $row->getId());
        self::assertEqualsCanonicalizing([Reference::STATUS_WAITING_FOR_RETRY, Reference::STATUS_ACCEPTED], $this->statuses($row));
        self::assertSame(SurveyStateInterface::STATE_INVITATION_SENT, $this->surveyState($row));
    }

    public function testAcceptedResponseRecordsNotifyId(): void
    {
        $row = $this->pendingInvitation();

        $this->subscriber->onMessageHandled(new WorkerMessageHandledEvent(
            $this->envelope($row)->with(new HandledStamp(['id' => '740e5834-3a29-46b4-9a6f-16142fde533a'], 'handler')),
            'async_notify',
        ));

        $this->entityManager->clear();
        $responses = $this->entityManager->find(SurveyNotification::class, $row->getId())->getNotifyApiResponses();
        self::assertSame('740e5834-3a29-46b4-9a6f-16142fde533a', $responses->first()->getNotifyId());
    }

    public function testFailedResponseHasNoNotifyId(): void
    {
        $row = $this->pendingInvitation();

        $this->subscriber->onMessageFailed($this->failedEvent($this->envelope($row), new \RuntimeException('Connection timed out'), willRetry: false));

        $this->entityManager->clear();
        $responses = $this->entityManager->find(SurveyNotification::class, $row->getId())->getNotifyApiResponses();
        self::assertNull($responses->first()->getNotifyId());
    }

    public function testNonApiFailureThatWillNotBeRetriedIsFailed(): void
    {
        $row = $this->pendingInvitation();

        $this->subscriber->onMessageFailed($this->failedEvent($this->envelope($row), new \RuntimeException('Connection timed out'), willRetry: false));

        $this->entityManager->clear();
        $row = $this->entityManager->find(SurveyNotification::class, $row->getId());
        self::assertSame([Reference::STATUS_FAILED], $this->statuses($row));
        self::assertSame(SurveyStateInterface::STATE_INVITATION_FAILED, $this->surveyState($row));
    }

    public function testUnrecoverableNonApiFailureIsFailed(): void
    {
        $row = $this->pendingInvitation();
        $refusal = new UnrecoverableMessageHandlingException('SMS per-number daily cap reached');

        // Middleware refusals arrive bare (not wrapped in a HandlerFailedException) and are never retried
        $event = new WorkerMessageFailedEvent($this->envelope($row), 'async_notify', $refusal);
        $this->subscriber->onMessageFailed($event);

        $this->entityManager->clear();
        $row = $this->entityManager->find(SurveyNotification::class, $row->getId());
        self::assertSame([Reference::STATUS_FAILED], $this->statuses($row));
    }

    private function pendingInvitation(): SurveyNotification
    {
        $survey = (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2026-08-01'))->setSurveyPeriodEnd(new \DateTime('2026-08-07'))
            ->setIsNorthernIreland(false)->setRegistrationMark('AB12CDE')
            ->setPasscodeUser((new PasscodeUser())->setUsername('subscriber-test')->setPlainPassword('x'))
            ->setState(SurveyStateInterface::STATE_INVITATION_PENDING);
        $row = (new SurveyNotification())->setType(NotificationType::INVITE)->setChannel(NotificationChannel::EMAIL)->setSentAt(new \DateTime());
        $survey->addNotification($row);
        $this->entityManager->persist($survey);
        $this->entityManager->persist($row);
        $this->entityManager->flush();

        return $row;
    }

    private function envelope(SurveyNotification $row): Envelope
    {
        return new Envelope(new Email('invite', SurveyNotification::class, $row->getId(), 'test@example.com', 'template-id'));
    }

    private function failedEvent(Envelope $envelope, \Throwable $failure, bool $willRetry): WorkerMessageFailedEvent
    {
        // Handler exceptions reach the subscriber wrapped, as Messenger's HandleMessageMiddleware throws them
        $event = new WorkerMessageFailedEvent($envelope, 'async_notify', new HandlerFailedException($envelope, [$failure]));
        if ($willRetry) {
            $event->setForRetry();
        }

        return $event;
    }

    private function surveyState(SurveyNotification $row): ?string
    {
        return $this->entityManager->find(Survey::class, $row->getSurvey()->getId())->getState();
    }

    /** @return list<string> */
    private function statuses(SurveyNotification $row): array
    {
        return array_values(array_map(
            fn($response) => $response->getData()[AlphagovNotifyMessengerSubscriber::STATUS_KEY],
            $row->getNotifyApiResponses()->toArray(),
        ));
    }
}
