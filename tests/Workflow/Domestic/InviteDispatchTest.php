<?php
namespace App\Tests\Workflow\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Embeddable\LongAddress;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class InviteDispatchTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    /** @var array<Envelope> */
    private array $envelopes = [];

    protected function setUp(): void
    {
        $container = static::getContainer();
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function (object $m, array $s = []) {
            $this->envelopes[] = new Envelope($m, $s);
            return end($this->envelopes);
        });
        $container->set('app.test.message_bus', $bus);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([]);
    }

    public function testPersistingANewSurveyInvitesViaTheDispatcherAndRecordsARow(): void
    {
        $survey = (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2026-08-25'))
            ->setSurveyPeriodEnd(new \DateTime('2026-08-31'))
            ->setIsNorthernIreland(false)
            ->setRegistrationMark('AB01ABC')
            ->setInvitationAddress((new LongAddress())->setLine1('Co')->setLine2('1 Road')->setPostcode('BA1 1HR'))
            ->setPasscodeUser((new PasscodeUser())->setUsername('10000003')->setPlainPassword('x'));

        $this->entityManager->persist($survey);
        $this->entityManager->flush();
        $id = $survey->getId();
        $this->entityManager->clear();

        $survey = $this->entityManager->find(Survey::class, $id);
        self::assertSame(SurveyStateInterface::STATE_INVITATION_PENDING, $survey->getState());
        self::assertCount(1, $survey->getNotificationsOfType(NotificationType::INVITE, NotificationChannel::LETTER));
        self::assertCount(1, $this->envelopes);
        self::assertInstanceOf(Letter::class, $this->envelopes[0]->getMessage());
        self::assertSame(4 * 3600 * 1000, $this->envelopes[0]->last(DelayStamp::class)->getDelay());
    }
}
