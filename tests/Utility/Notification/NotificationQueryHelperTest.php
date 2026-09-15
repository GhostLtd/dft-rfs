<?php

namespace App\Tests\Utility\Notification;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Tests\DataFixtures\Notification\ReminderScenarioFixtures;
use App\Tests\Utility\Reminder\ResolvesFixtureRefsTrait;
use App\Utility\Notification\NotificationQueryHelper;
use App\Utility\Notification\NotificationType;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NotificationQueryHelperTest extends KernelTestCase
{
    use ResolvesFixtureRefsTrait;

    private EntityManagerInterface $entityManager;
    private ReferenceRepository $refs;
    private NotificationQueryHelper $helper;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->refs = $container->get(DatabaseToolCollection::class)->get()->loadFixtures([ReminderScenarioFixtures::class])->getReferenceRepository();
        $this->helper = new NotificationQueryHelper($this->entityManager);
    }

    public function testNoneExcludesSurveysWithARowOfThatType(): void
    {
        $qb = $this->entityManager->createQueryBuilder()->select('s')->from(Survey::class, 's');
        $this->helper->none($qb, 's', SurveyNotification::class, NotificationType::REMINDER_1);
        $this->helper->none($qb, 's', SurveyNotification::class, NotificationType::REMINDER_2);

        $refs = $this->refsOf(Survey::class, $qb->getQuery()->getResult());
        self::assertNotContains('r1:already-sent', $refs);
        self::assertNotContains('r1:blocked-by-r2', $refs);
        self::assertNotContains('r2:due', $refs);
        self::assertContains('r1:due', $refs);
    }

    public function testLatestAtOrBeforeUsesTheNewestRowOfThatType(): void
    {
        $cutoff = new \DateTime('2026-09-03 21:00:00'); // NOW - 7d + 12h
        $qb = $this->entityManager->createQueryBuilder()->select('s')->from(Survey::class, 's');
        $this->helper->latestAtOrBefore($qb, 's', SurveyNotification::class, NotificationType::INVITE, $cutoff);

        $refs = $this->refsOf(Survey::class, $qb->getQuery()->getResult());
        self::assertContains('r1:due', $refs);
        self::assertContains('r1:tolerance', $refs);
        self::assertNotContains('r1:invite-too-recent', $refs);
        self::assertNotContains('r1:resent-invite-defers', $refs, 'an old invite followed by a recent one: the LATEST is too recent');
    }

    public function testBothHelpersComposeOnOneQueryBuilder(): void
    {
        $qb = $this->entityManager->createQueryBuilder()->select('s')->from(Survey::class, 's');
        $this->helper->none($qb, 's', SurveyNotification::class, NotificationType::REMINDER_1);
        $this->helper->latestAtOrBefore($qb, 's', SurveyNotification::class, NotificationType::INVITE, new \DateTime('2026-09-03 21:00:00'));
        $this->helper->latestAtOrBefore($qb, 's', SurveyNotification::class, NotificationType::REMINDER_1, new \DateTime('2026-08-27 21:00:00'));

        self::assertSame(
            ['none_type_reminder_1', 'latest_type_invite', 'latest_cutoff_invite', 'latest_type_reminder_1', 'latest_cutoff_reminder_1'],
            array_map(fn($p) => $p->getName(), $qb->getParameters()->toArray()),
            'each call binds its own parameters, so several calls compose on one builder',
        );
        self::assertCount(0, $qb->getQuery()->getResult(), 'self-contradictory by construction: an invite AND a reminder-1 old enough AND no reminder-1 at all can never be true of the same survey');
    }
}
