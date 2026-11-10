<?php

namespace App\Tests\NewFunctional\Admin;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\NotifyApiResponse;
use App\Entity\NotifyCallback;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;
use App\EventSubscriber\AlphagovNotifyMessengerSubscriber;
use App\Tests\NewFunctional\AbstractFunctionalTestCase;
use App\Tests\Support\DeliveryProblemSurveysTrait;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;

/**
 * Delivery problems (send-time failures, and fatal Notify callbacks) on the admin survey view, and marking them seen.
 */
class DeliveryProblemsTest extends AbstractFunctionalTestCase
{
    use DeliveryProblemSurveysTrait;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([], [
            'hostname' => 'rfs-admin.localhost',
            'env' => ['TEST_GOOGLE_IAP_AUTH_USER' => 'test@example.com'],
        ]);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function surveyWithResponse(string $status, ?string $callbackStatus = null): array
    {
        $survey = (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2026-08-01'))->setSurveyPeriodEnd(new \DateTime('2026-08-07'))
            ->setIsNorthernIreland(false)->setRegistrationMark('AB12CDE')->setInvitationEmails('someone@example.com')->setQualityAssured(false)
            ->setPasscodeUser((new PasscodeUser())->setUsername('delivery-test')->setPlainPassword('x'))
            ->setState(SurveyStateInterface::STATE_INVITATION_SENT);
        $row = (new SurveyNotification())->setType(NotificationType::INVITE)->setChannel(NotificationChannel::EMAIL)->setSentAt(new \DateTime('2026-08-01 10:00'));
        $survey->addNotification($row);
        $response = (new NotifyApiResponse())
            ->setEventName('invite')->setMessageClass(Email::class)->setEndpoint('/v2/notifications/email')
            ->setRecipient('someone@example.com')
            ->setData([AlphagovNotifyMessengerSubscriber::STATUS_KEY => $status, 'errors' => []])
            ->setNotifyId('740e5834-3a29-46b4-9a6f-16142fde533a')->setTimestamp(new \DateTime('2026-08-01 10:00'));
        $row->addNotifyApiResponse($response);

        $this->entityManager->persist($survey);
        $this->entityManager->persist($row);
        $this->entityManager->persist($response);

        if ($callbackStatus !== null) {
            $this->entityManager->persist((new NotifyCallback())
                ->setResponse($response)->setNotifyId('740e5834-3a29-46b4-9a6f-16142fde533a')
                ->setType(NotifyCallback::TYPE_DELIVERY_RECEIPT)->setStatus($callbackStatus)
                ->setOccurredAt(new \DateTime('2026-08-01 10:05'))->setReceivedAt(new \DateTime('2026-08-01 10:06')));
        }

        $this->entityManager->flush();

        return [$survey, $response];
    }

    private function viewSurvey(Survey $survey): void
    {
        $this->client->request('GET', "/csrgt/surveys/{$survey->getId()}");
        self::assertResponseIsSuccessful();
    }

    private function invitationTab(): string
    {
        return $this->client->getCrawler()->filter('#tab-invitation-details')->text();
    }

    private function problemSeenAt(NotifyApiResponse $response): ?\DateTime
    {
        $this->entityManager->clear();
        return $this->entityManager->find(NotifyApiResponse::class, $response->getId())->getProblemSeenAt();
    }

    public function testFatalCallbackIsShownAndCanBeMarkedSeenAndUnseen(): void
    {
        [$survey, $response] = $this->surveyWithResponse(Reference::STATUS_ACCEPTED, NotifyCallback::STATUS_PERMANENT_FAILURE);

        $this->viewSurvey($survey);
        self::assertStringContainsString('permanent failure', $this->invitationTab());
        self::assertStringContainsString('1 delivery problem (1 unseen)', $this->invitationTab());

        $this->client->submitForm('Mark delivery problems as seen');
        self::assertNotNull($this->problemSeenAt($response));
        self::assertStringContainsString('1 delivery problem (0 unseen)', $this->invitationTab());
        self::assertMatchesRegularExpression('#\(seen \d{2}/\d{2}/\d{4} #', $this->invitationTab());

        $this->client->submitForm('Mark delivery problems as unseen');
        self::assertNull($this->problemSeenAt($response));
    }

    public function testSeveralProblemsArePlural(): void
    {
        $surveys = $this->createDeliveryProblemSurveys($this->entityManager);

        $this->viewSurvey($surveys['MIXED']);
        self::assertStringContainsString('2 delivery problems (1 unseen)', $this->invitationTab());
    }

    public function testReturnedLetterShowsWhenWeWereNotified(): void
    {
        $surveys = $this->createDeliveryProblemSurveys($this->entityManager);

        $this->viewSurvey($surveys['AFTER2']);
        self::assertStringContainsString('letter returned', $this->invitationTab());
        self::assertStringContainsString('notified 02/08/2026', $this->invitationTab());
    }

    public function testSendTimeFailureCountsAsAProblem(): void
    {
        [$survey] = $this->surveyWithResponse(Reference::STATUS_FAILED);

        $this->viewSurvey($survey);
        self::assertStringContainsString('1 delivery problem (1 unseen)', $this->invitationTab());
    }

    public function testTemporaryFailureIsShownButIsNotAProblem(): void
    {
        [$survey] = $this->surveyWithResponse(Reference::STATUS_ACCEPTED, NotifyCallback::STATUS_TEMPORARY_FAILURE);

        $this->viewSurvey($survey);
        self::assertStringContainsString('temporary failure', $this->invitationTab());
        self::assertStringNotContainsString('delivery problem', $this->invitationTab());
        self::assertCount(0, $this->client->getCrawler()->selectButton('Mark delivery problems as seen'));
    }

    public function testSmsCapRefusalIsShownButIsNotAProblem(): void
    {
        [$survey] = $this->surveyWithResponse(Reference::STATUS_REFUSED);

        $this->viewSurvey($survey);
        self::assertSame('refused', trim($this->client->getCrawler()->filter('#tab-invitation-details .govuk-tag--grey')->text()));
        self::assertStringNotContainsString('delivery problem', $this->invitationTab());
    }

    public function testDeliveredIsShown(): void
    {
        [$survey] = $this->surveyWithResponse(Reference::STATUS_ACCEPTED, NotifyCallback::STATUS_DELIVERED);

        $this->viewSurvey($survey);
        self::assertStringContainsString('delivered', $this->invitationTab());
        self::assertStringNotContainsString('delivery problem', $this->invitationTab());
    }
}
