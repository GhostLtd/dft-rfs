<?php

namespace App\Tests\Functional\Utility;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey;
use App\Tests\Functional\AbstractFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractCleanupTest extends AbstractFunctionalTest
{
    protected EntityManagerInterface $entityManager;

    public function setUp()
    {
        parent::setUp();

        $container = self::$kernel->getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    protected function getSurveyAndSetState(string $auditOffset, string $state): Survey
    {
        /** @var Survey $survey */
        $survey = $this->entityManager
            ->getRepository(Survey::class)
            ->findOneBy([]);

        $this->entityManager->flush();

        $survey->setState($state);

        // So that the "new" -> "invitation pending" state change doesn't have the exact same timestamp as the
        // "invitation pending" -> $state one
        sleep(1);

        $this->entityManager->flush();

        /**
         * @var AuditLog[] $auditLogs
         */
        $auditLogs = $this->entityManager
            ->getRepository(AuditLog::class)
            ->findBy(['entityId' => $survey->getId()]);

        // We need the auditLog state change timestamps to be in the past, so we artificially modify them here so
        // that we can test various scenarios
        foreach($auditLogs as $auditLog) {
            $modifiedTimestamp = (clone $auditLog->getTimestamp())->modify($auditOffset);
            $auditLog->setTimestamp($modifiedTimestamp);
        }

        $this->entityManager->flush();

        return $survey;
    }
}