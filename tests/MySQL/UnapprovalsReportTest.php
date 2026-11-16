<?php

namespace App\Tests\MySQL;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Repository\AuditLog\AuditLogRepository;
use App\Utility\AuditEntityLogger\SurveyStateLogger;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The unapprovals report (approvals later reversed, by approver) uses GROUP_CONCAT(... SEPARATOR ...), MySQL-only
 * syntax, and the JSON lookups of GHOST_JSON_GET.
 *
 * N.B. Production's group_concat_max_len is 1024, so "ids" is silently truncated beyond ~27 surveys per user; the
 * report template only shows the count, so that doesn't matter today.
 */
class UnapprovalsReportTest extends KernelTestCase
{
    use RunsOnMySql;

    public function testCountsApprovalsLaterReversedByApprover(): void
    {
        static::bootKernel();
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([]);
        $entityManager = $container->get('doctrine.orm.audit_log_entity_manager');

        $reversed = '1f0c2a4e-7b3d-6e10-9a5b-000000000001';
        $reversedTwice = '1f0c2a4e-7b3d-6e10-9a5b-000000000002';
        $stillApproved = '1f0c2a4e-7b3d-6e10-9a5b-000000000003';

        foreach ([
            [$reversed, 'alice', 'closed', 'approved', '2021-05-03 10:00'],
            [$reversed, 'bob', 'approved', 'closed', '2021-05-04 10:00'],
            [$reversedTwice, 'alice', 'closed', 'approved', '2021-05-05 10:00'],
            [$reversedTwice, 'bob', 'approved', 'closed', '2021-05-06 10:00'],
            [$reversedTwice, 'carol', 'closed', 'approved', '2021-05-07 10:00'],
            [$reversedTwice, 'bob', 'approved', 'closed', '2021-05-08 10:00'],
            [$stillApproved, 'carol', 'closed', 'approved', '2021-05-03 10:00'],
            // Outside the report's dates
            [$reversed, 'dave', 'closed', 'approved', '2021-08-01 10:00'],
            [$reversed, 'bob', 'approved', 'closed', '2021-08-02 10:00'],
        ] as [$surveyId, $username, $from, $to, $timestamp]) {
            $entityManager->persist((new AuditLog())
                ->setEntityId($surveyId)
                ->setEntityClass(DomesticSurvey::class)
                ->setCategory(SurveyStateLogger::CATEGORY)
                ->setUsername($username)
                ->setTimestamp(new \DateTime($timestamp))
                ->setData(['from' => $from, 'to' => $to]));
        }
        $entityManager->flush();

        $stats = $container->get(AuditLogRepository::class)->getUnapprovalReportStats(
            DomesticSurvey::class,
            new \DateTime('2021-05-01'),
            new \DateTime('2021-06-01'),
        );

        $this->assertSame(['alice', 'carol'], array_column($stats, 'username'));
        $byUser = array_column($stats, null, 'username');

        $this->assertEquals(2, $byUser['alice']['rejectedApprovalCount']);
        $this->assertEqualsCanonicalizing([$reversed, $reversedTwice], explode("\n", $byUser['alice']['ids']));
        $this->assertEquals(1, $byUser['carol']['rejectedApprovalCount'], "carol's other approval was never reversed");
        $this->assertSame($reversedTwice, $byUser['carol']['ids']);
    }
}
