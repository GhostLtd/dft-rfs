<?php

namespace App\Tests\NewFunctional;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\NotifyApiResponse;
use App\Entity\NotifyCallback;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;
use App\EventSubscriber\AlphagovNotifyMessengerSubscriber;
use App\Tests\DataFixtures\MaintenanceLockFixtures;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * GOV.UK Notify callbacks (delivery receipts and returned letters). See local/2026-11-04-notify-callbacks-research.md.
 *
 * Notify sends a callback again after a network error, a 5xx or a 429, but drops any other 4xx: so a temporary
 * problem must never surface as a 4xx.
 */
class NotifyCallbackTest extends WebTestCase
{
    private const string TOKEN = 'test-callback-token';
    private const string EMAIL_NOTIFY_ID = '740e5834-3a29-46b4-9a6f-16142fde533a';
    private const string LETTER_NOTIFY_ID = '2d1b7c1e-5b0c-4a8e-9a43-6d1bc6b8f2a1';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->setServerParameter('HTTP_HOST', $_ENV['FRONTEND_HOSTNAME']);
        $this->client->setServerParameter('HTTPS', 'on');
    }

    private function loadFixtures(array $fixtures = []): void
    {
        static::getContainer()->get(DatabaseToolCollection::class)->get()->loadFixtures($fixtures);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    /** An accepted response to a CSRGT invitation, as the Notify subscriber records it */
    private function acceptedResponse(string $messageClass, string $notifyId, ?\DateTime $problemSeenAt = null): NotifyApiResponse
    {
        $survey = (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2026-08-01'))->setSurveyPeriodEnd(new \DateTime('2026-08-07'))
            ->setIsNorthernIreland(false)->setRegistrationMark('AB12CDE')
            ->setPasscodeUser((new PasscodeUser())->setUsername('callback-'.substr($notifyId, 0, 8))->setPlainPassword('x'))
            ->setState(SurveyStateInterface::STATE_INVITATION_SENT);
        $row = (new SurveyNotification())->setType(NotificationType::INVITE)
            ->setChannel($messageClass === Letter::class ? NotificationChannel::LETTER : NotificationChannel::EMAIL)
            ->setSentAt(new \DateTime());
        $survey->addNotification($row);
        $response = (new NotifyApiResponse())
            ->setEventName('invite')->setMessageClass($messageClass)->setEndpoint('/v2/notifications/email')
            ->setData(['id' => $notifyId, AlphagovNotifyMessengerSubscriber::STATUS_KEY => Reference::STATUS_ACCEPTED])
            ->setNotifyId($notifyId)->setProblemSeenAt($problemSeenAt)->setTimestamp(new \DateTime());
        $row->addNotifyApiResponse($response);

        $this->entityManager->persist($survey);
        $this->entityManager->persist($row);
        $this->entityManager->persist($response);
        $this->entityManager->flush();

        return $response;
    }

    private function deliveryReceipt(string $status, string $id = self::EMAIL_NOTIFY_ID): array
    {
        return [
            'id' => $id,
            'reference' => 'App\\Entity\\Domestic\\Survey::1ef9::9f86d08',
            'to' => 'someone@example.com',
            'status' => $status,
            'created_at' => '2026-11-05T09:00:00.000000Z',
            'completed_at' => '2026-11-05T09:01:30.000000Z',
            'sent_at' => '2026-11-05T09:00:05.000000Z',
            'notification_type' => 'email',
            'template_id' => 'f33517ff-2a88-4f6e-b855-c550268ce08a',
            'template_version' => 1,
        ];
    }

    private function returnedLetter(string $id = self::LETTER_NOTIFY_ID): array
    {
        return [
            'notification_id' => $id,
            'reference' => 'App\\Entity\\Domestic\\Survey::1ef9::9f86d08',
            'date_sent' => '2026-10-01T15:30:00.000000Z',
            'sent_by' => 'API',
            'template_name' => 'CSRGT invitation',
            'template_id' => 'f33517ff-2a88-4f6e-b855-c550268ce08a',
            'template_version' => 1,
            'spreadsheet_file_name' => null,
            'spreadsheet_row_number' => null,
            'upload_letter_file_name' => null,
        ];
    }

    private function post(string $type, array|string $body, ?string $token = self::TOKEN): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = "Bearer {$token}";
        }

        $this->client->request('POST', "/notify/callback/{$type}", server: $server, content: is_string($body) ? $body : json_encode($body));
    }

    /** @return list<NotifyCallback> */
    private function callbacks(): array
    {
        $this->entityManager->clear();
        return $this->entityManager->getRepository(NotifyCallback::class)->findAll();
    }

    private function reloaded(NotifyApiResponse $response): NotifyApiResponse
    {
        $this->entityManager->clear();
        return $this->entityManager->find(NotifyApiResponse::class, $response->getId());
    }

    public static function badTokens(): array
    {
        return [
            'missing' => [null],
            'wrong' => ['not-the-token'],
        ];
    }

    #[DataProvider('badTokens')]
    public function testBadTokenIsRejected(?string $token): void
    {
        $this->loadFixtures();
        $this->acceptedResponse(Email::class, self::EMAIL_NOTIFY_ID);

        $this->post('delivery-receipt', $this->deliveryReceipt(NotifyCallback::STATUS_PERMANENT_FAILURE), $token);

        self::assertResponseStatusCodeSame(401);
        self::assertSame([], $this->callbacks());
    }

    public static function malformedPayloads(): array
    {
        $receipt = [
            'id' => self::EMAIL_NOTIFY_ID,
            'status' => NotifyCallback::STATUS_DELIVERED,
            'notification_type' => 'email',
        ];

        return [
            'not JSON' => ['delivery-receipt', '{not json'],
            'not an object' => ['delivery-receipt', '"a string"'],
            'receipt without an id' => ['delivery-receipt', json_encode(array_diff_key($receipt, ['id' => true]))],
            'receipt with a non-UUID id' => ['delivery-receipt', json_encode(['id' => 'abc'] + $receipt)],
            'receipt with an unknown status' => ['delivery-receipt', json_encode(['status' => 'sending'] + $receipt)],
            'returned letter without a notification_id' => ['returned-letter', json_encode(['reference' => 'x'])],
        ];
    }

    #[DataProvider('malformedPayloads')]
    public function testMalformedPayloadIsRejected(string $type, string $body): void
    {
        $this->loadFixtures();

        $this->post($type, $body);

        self::assertResponseStatusCodeSame(400);
        self::assertSame([], $this->callbacks());
    }

    public function testUnmatchedCallbackIsAcceptedAndNotStored(): void
    {
        $this->loadFixtures();
        $this->acceptedResponse(Email::class, self::EMAIL_NOTIFY_ID);

        $this->post('delivery-receipt', $this->deliveryReceipt(NotifyCallback::STATUS_DELIVERED, 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee'));

        self::assertResponseStatusCodeSame(204);
        self::assertSame([], $this->callbacks());
    }

    public function testFatalDeliveryReceiptIsStoredWithoutTheRecipientAndMarksTheProblemUnseen(): void
    {
        $this->loadFixtures();
        $response = $this->acceptedResponse(Email::class, self::EMAIL_NOTIFY_ID, problemSeenAt: new \DateTime('2026-11-01'));

        $this->post('delivery-receipt', $this->deliveryReceipt(NotifyCallback::STATUS_PERMANENT_FAILURE));

        self::assertResponseStatusCodeSame(204);
        $callbacks = $this->callbacks();
        self::assertCount(1, $callbacks);
        self::assertSame($response->getId(), $callbacks[0]->getResponse()->getId());
        self::assertSame(NotifyCallback::TYPE_DELIVERY_RECEIPT, $callbacks[0]->getType());
        self::assertSame(NotifyCallback::STATUS_PERMANENT_FAILURE, $callbacks[0]->getStatus());
        self::assertSame(self::EMAIL_NOTIFY_ID, $callbacks[0]->getNotifyId());
        self::assertArrayNotHasKey('to', $callbacks[0]->getPayload());
        self::assertSame('email', $callbacks[0]->getPayload()['notification_type']);
        self::assertNotNull($callbacks[0]->getOccurredAt());
        self::assertNotNull($callbacks[0]->getReceivedAt());
        self::assertNull($this->reloaded($response)->getProblemSeenAt());
    }

    public function testNonFatalDeliveryReceiptLeavesSeenStateAlone(): void
    {
        $this->loadFixtures();
        $response = $this->acceptedResponse(Email::class, self::EMAIL_NOTIFY_ID, problemSeenAt: new \DateTime('2026-11-01'));

        $this->post('delivery-receipt', $this->deliveryReceipt(NotifyCallback::STATUS_TEMPORARY_FAILURE));

        self::assertResponseStatusCodeSame(204);
        self::assertCount(1, $this->callbacks());
        self::assertNotNull($this->reloaded($response)->getProblemSeenAt());
    }

    public function testReplayedFatalCallbackIsStoredOnceAndLeavesItSeen(): void
    {
        $this->loadFixtures();
        $response = $this->acceptedResponse(Email::class, self::EMAIL_NOTIFY_ID);

        $this->post('delivery-receipt', $this->deliveryReceipt(NotifyCallback::STATUS_PERMANENT_FAILURE));
        self::assertResponseStatusCodeSame(204);

        // An admin marks it seen; then Notify sends the same callback again (e.g. after our response timed out)
        $this->reloaded($response)->setProblemSeenAt(new \DateTime('2026-11-05 10:00:00'));
        $this->entityManager->flush();

        $this->post('delivery-receipt', $this->deliveryReceipt(NotifyCallback::STATUS_PERMANENT_FAILURE));

        self::assertResponseStatusCodeSame(204);
        self::assertCount(1, $this->callbacks());
        self::assertNotNull($this->reloaded($response)->getProblemSeenAt());
    }

    public function testReturnedLetterIsStoredOnceWithAFixedStatus(): void
    {
        $this->loadFixtures();
        $response = $this->acceptedResponse(Letter::class, self::LETTER_NOTIFY_ID);

        $this->post('returned-letter', $this->returnedLetter());
        self::assertResponseStatusCodeSame(204);
        $this->post('returned-letter', $this->returnedLetter());
        self::assertResponseStatusCodeSame(204);

        $callbacks = $this->callbacks();
        self::assertCount(1, $callbacks);
        self::assertSame($response->getId(), $callbacks[0]->getResponse()->getId());
        self::assertSame(NotifyCallback::TYPE_RETURNED_LETTER, $callbacks[0]->getType());
        self::assertSame(NotifyCallback::STATUS_RETURNED, $callbacks[0]->getStatus());
        self::assertSame('API', $callbacks[0]->getPayload()['sent_by']);
        self::assertTrue($callbacks[0]->isFatal());
    }

    public function testMaintenanceLockGivesServiceUnavailableSoNotifySendsItAgain(): void
    {
        $this->loadFixtures([MaintenanceLockFixtures::class]);
        $this->acceptedResponse(Email::class, self::EMAIL_NOTIFY_ID);

        $this->post('delivery-receipt', $this->deliveryReceipt(NotifyCallback::STATUS_PERMANENT_FAILURE));

        self::assertResponseStatusCodeSame(503);
        self::assertSame([], $this->callbacks());
    }

    /**
     * The route is stateless: with debug on, Symfony turns any session use into a 500 (and in prod a callback would
     * start a session for nothing). Notify sends no cookies, so there's never a session to use.
     */
    public function testCallbackDoesNotUseTheSession(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient(['debug' => true]);
        $this->client->setServerParameter('HTTP_HOST', $_ENV['FRONTEND_HOSTNAME']);
        $this->client->setServerParameter('HTTPS', 'on');
        $this->loadFixtures();
        $this->acceptedResponse(Email::class, self::EMAIL_NOTIFY_ID);

        $sessionStarted = null;
        static::getContainer()->get('event_dispatcher')->addListener(KernelEvents::RESPONSE, function (ResponseEvent $event) use (&$sessionStarted) {
            if ($event->isMainRequest()) {
                // hasSession(true) is false while the session is an uninitialised factory
                $request = $event->getRequest();
                $sessionStarted = $request->hasSession(true) && $request->getSession()->isStarted();
            }
        }, -999); // before SessionListener (-1000) saves and closes it

        $this->post('delivery-receipt', $this->deliveryReceipt(NotifyCallback::STATUS_PERMANENT_FAILURE));

        self::assertResponseStatusCodeSame(204);
        self::assertFalse($sessionStarted);
        self::assertCount(1, $this->callbacks());
    }

    public function testProcessingFailureGivesAServerError(): void
    {
        $this->loadFixtures();
        $this->acceptedResponse(Email::class, self::EMAIL_NOTIFY_ID);
        // Simulate a database failure mid-request. The table is put back afterwards: the test database (and liip's
        // backups of it) are shared with the rest of the run.
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('ALTER TABLE notify_callback RENAME TO notify_callback_hidden');

        try {
            $this->client->catchExceptions(true);
            $this->post('delivery-receipt', $this->deliveryReceipt(NotifyCallback::STATUS_PERMANENT_FAILURE));
        } finally {
            $connection->executeStatement('ALTER TABLE notify_callback_hidden RENAME TO notify_callback');
        }

        self::assertResponseStatusCodeSame(500);
        self::assertSame([], $this->callbacks());
    }
}
