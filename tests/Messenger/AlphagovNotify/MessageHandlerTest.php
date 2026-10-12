<?php

namespace App\Tests\Messenger\AlphagovNotify;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\Embeddable\Address;
use App\Entity\NotifyApiResponse;
use App\Entity\RoRoUser;
use App\Entity\SurveyStateInterface;
use App\EventSubscriber\AlphagovNotifyMessengerSubscriber;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use App\Tests\DataFixtures\AlphagovNotify\MessageHandlerSurveyFixtures;
use App\Tests\DataFixtures\RoRo\CountryFixtures;
use App\Tests\DataFixtures\RoRo\OperatorRouteFixtures;
use App\Tests\DataFixtures\RoRo\UserFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Sends real requests to the GOV.UK Notify API (via the async_notify consumer), so it needs a real (test) key in
 * ALPHAGOV_NOTIFY_API_KEY and is excluded from default runs by phpunit.xml.dist. Run it explicitly with:
 *   vendor/bin/phpunit --group notify-api
 */
#[Group('notify-api')]
class MessageHandlerTest extends AbstractFunctionalTestCase
{
    protected MessageBusInterface $bus;
    private EntityManagerInterface $entityManager;
    private PersonalisationHelper $personalisationHelper;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();

        $this->bus = $container->get('messenger.bus.default');
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->personalisationHelper = $container->get(PersonalisationHelper::class);

        $this->entityManager->getConnection()->createQueryBuilder()
            ->delete('messenger_messages')
            ->executeStatement();
    }

    public function testSendLetterAccepted()
    {
        $survey = $this->getSurvey();

        $this->bus->dispatch($this->getInviteLetter($survey, $this->getValidTestAddress()));

        $this->runAsyncNotifyMessengerConsumer();

        /** @var Survey $survey */
        $survey = $this->entityManager->find($survey::class, $survey->getId());
        /** @var SurveyNotification $row */
        $row = $survey->getNotificationsOfType(NotificationType::INVITE, NotificationChannel::LETTER)[0];
        $apiResponses = $row->getNotifyApiResponsesMatching(NotificationType::INVITE->value, Letter::class);

        /** @var NotifyApiResponse|false $response */
        $response = current($apiResponses);

        if ($response === false) {
            $this->fail('No notify api responses found');
        }

        self::assertSame(Reference::STATUS_ACCEPTED, $response->getData()[AlphagovNotifyMessengerSubscriber::STATUS_KEY]);
    }

    public function testSendLetterValidationError()
    {
        $survey = $this->getSurvey();

        $this->bus->dispatch($this->getInviteLetter($survey, $this->getInvalidTestAddress()));
        $this->runAsyncNotifyMessengerConsumer();

        /** @var Survey $survey */
        $survey = $this->entityManager->find($survey::class, $survey->getId());
        /** @var SurveyNotification $row */
        $row = $survey->getNotificationsOfType(NotificationType::INVITE, NotificationChannel::LETTER)[0];
        $apiResponses = $row->getNotifyApiResponsesMatching(NotificationType::INVITE->value, Letter::class);

        /** @var NotifyApiResponse|false $response */
        $response = current($apiResponses);

        if ($response === false) {
            $this->fail('No notify api responses found');
        }

        self::assertSame(Reference::STATUS_FAILED, $response->getData()[AlphagovNotifyMessengerSubscriber::STATUS_KEY]);
    }

    public function testAcceptedInviteTransitionsSurvey(): void
    {
        $survey = $this->getSurvey();

        $this->bus->dispatch($this->getInviteLetter($survey, $this->getValidTestAddress()));
        $this->runAsyncNotifyMessengerConsumer();

        /** @var Survey $survey */
        $survey = $this->entityManager->find($survey::class, $survey->getId());

        self::assertSame(SurveyStateInterface::STATE_INVITATION_SENT, $survey->getState());
    }

    public function testRoRoUserOriginatedMessageAttachesResponseToUser(): void
    {
        $this->loadFixtures([CountryFixtures::class, OperatorRouteFixtures::class, UserFixtures::class]);
        /** @var RoRoUser $user */
        $user = $this->fixtureReferenceRepository->getReference('roro:user:1', RoRoUser::class);

        $this->bus->dispatch(new Email(
            NotificationType::REMINDER_1->value,
            RoRoUser::class,
            $user->getId(),
            $user->getEmail(),
            Reference::EMAIL_RORO_REMINDER_1,
            []
        ));

        $this->runAsyncNotifyMessengerConsumer();

        /** @var RoRoUser $user */
        $user = $this->entityManager->find($user::class, $user->getId());
        $apiResponses = $user->getNotifyApiResponsesMatching(NotificationType::REMINDER_1->value, Email::class);

        self::assertCount(1, $apiResponses, 'the response attaches to the RoRo user');
    }

    protected function runAsyncNotifyMessengerConsumer(): void
    {
        $this->runCommand(
            'messenger:consume',
            [
                '--env' => 'test',
                // we only want to run a single message
                '--limit' => 1,
                // ensure that it doesn't run for too long
                '--time-limit' => 3,
                'receivers' => ['async_notify'],
            ]
        );
    }

    protected function getValidTestAddress(): Address
    {
        return (new Address())
            ->setLine1('Unit Test Road')
            ->setLine2('Unit Test Place')
            ->setPostcode('BA111HR');
    }

    protected function getInvalidTestAddress(): Address
    {
        return (new Address())
            ->setLine1('Unit Test Road')
            ->setLine2('Unit Test Place')
            ->setPostcode('AB123CD');
    }

    protected function getInviteLetter(Survey $survey, Address $address): Letter
    {
        $notification = $survey->createNotification()
            ->setType(NotificationType::INVITE)
            ->setChannel(NotificationChannel::LETTER)
            ->setSentAt(new \DateTime());
        $survey->addNotification($notification);
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return new Letter(
            NotificationType::INVITE->value,
            $notification::class,
            $notification->getId(),
            $address,
            Reference::LETTER_DOMESTIC_SURVEY_INVITE,
            $this->personalisationHelper->getForEntity($survey)
        );
    }

    protected function getSurvey(): Survey
    {
        $this->loadFixtures([MessageHandlerSurveyFixtures::class]);
        /** @var Survey $survey */
        $survey = $this->fixtureReferenceRepository->getReference('survey:notify-message-handler', Survey::class);
        $survey->getPasscodeUser()->setPlainPassword('test');
        return $survey;
    }

    protected function runCommand($command, $options): void
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $options = array_merge(['command' => $command], $options);
        $input = new ArrayInput($options);
        $application->run($input, new NullOutput());
    }
}