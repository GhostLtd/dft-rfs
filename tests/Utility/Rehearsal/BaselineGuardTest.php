<?php

namespace App\Tests\Utility\Rehearsal;

use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use App\Entity\RoRo\Operator;
use App\Entity\RoRoUser;
use App\Tests\DataFixtures\Rehearsal\BaselineReferenceFixtures;
use App\Utility\Rehearsal\BaselineGuard;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BaselineGuardTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private BaselineGuard $guard;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([BaselineReferenceFixtures::class]);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->guard = $container->get(BaselineGuard::class);
    }

    public function testBaselineReferenceDataPasses(): void
    {
        self::assertSame([], $this->guard->problems());
    }

    public function testAnUnseededSurveyFails(): void
    {
        $this->entityManager->persist((new Survey())->setState('in-progress')->setRegistrationMark('AB12CDE')
            ->setIsNorthernIreland(false)->setSurveyPeriodStart(new \DateTime('2026-08-01'))->setSurveyPeriodEnd(new \DateTime('2026-08-07'))
            ->setPasscodeUser((new PasscodeUser())->setUsername('x')->setPlainPassword('x')));
        $this->entityManager->flush();

        self::assertNotSame([], $this->guard->problems());
    }

    public function testAnOperatorWithAUserFailsButOneWithoutIsBaseline(): void
    {
        $operator = $this->entityManager->getRepository(Operator::class)->findOneBy(['name' => 'Stena Line - Irish']);
        self::assertSame([], $this->guard->problems());

        $this->entityManager->persist((new RoRoUser())->setUsername('someone@example.com')->setOperator($operator));
        $this->entityManager->flush();

        self::assertNotSame([], $this->guard->problems());
    }

    public function testQueuedMessagesUsageAttemptsResponsesAndAuditRowsFail(): void
    {
        $c = $this->entityManager->getConnection();
        foreach ([
            fn() => $c->insert('messenger_messages', ['body' => 'x', 'headers' => '[]', 'queue_name' => 'govuk-notify', 'created_at' => '2026-09-10 09:00:00', 'available_at' => '2026-09-10 09:00:00']),
            fn() => $c->insert('sms_usage', ['financial_year' => 2026, 'sent' => 1]),
            fn() => $c->insert('sms_attempt', ['notification_id' => 'n-1', 'financial_year' => 2026, 'attempts' => 0]),
            fn() => $c->insert('sms_recipient_day', ['recipient_hmac' => str_repeat('a', 64), 'day' => '2026-09-10', 'sent' => 1]),
            fn() => $c->insert('audit_log', ['id' => 'a-1', 'category' => 'x', 'username' => 'x', 'entity_id' => 'e', 'entity_class' => 'c', 'timestamp' => '2026-09-10 09:00:00', 'data' => '[]']),
        ] as $i => $insert) {
            $c->beginTransaction();
            $insert();
            self::assertNotSame([], $this->guard->problems(), "insert #{$i} must be reported");
            $c->rollBack();
        }
    }
}
