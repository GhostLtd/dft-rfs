<?php

namespace App\Utility;

use App\Entity\PasscodeUser;
use App\Utility\AuditEntityLogger\AuditEntityLogger;
use App\Utility\AuditEntityLogger\ChangeSet;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Throwable;

class AuditLogger
{
    protected string $appEnvironment;
    protected EntityManagerInterface $auditLogEntityManager;
    protected LoggerInterface $log;
    protected Security $security;

    /** @var AuditEntityLogger[] */
    protected array $entityLoggers;

    protected array $entityChangeSets;

    public function __construct(EntityManagerInterface $auditLogEntityManager, LoggerInterface $log, Security $security, string $appEnvironment)
    {
        $this->appEnvironment = $appEnvironment;
        $this->auditLogEntityManager = $auditLogEntityManager;
        $this->entityLoggers = [];
        $this->log = $log;
        $this->security = $security;

        $this->entityChangeSets = [];
    }

    public function addAuditEntityLogger(AuditEntityLogger $entityLogger): void
    {
        $this->entityLoggers[] = $entityLogger;
    }

    public function log(EntityManagerInterface $entityManager): void
    {
        try {
            $user = $this->security->getUser();
            $username = $user ? ($user instanceof PasscodeUser ? 'survey-user' : $user->getUsername()) : '-';

            $this->processChanges($entityManager, $username);
        } catch (Throwable $e) {
            $this->log->error(sprintf("[AuditLogger] %s: %s (%s Line %s)", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
            if ($this->appEnvironment === 'dev') {
                throw $e;
            }
        }
    }

    protected function processChanges(EntityManagerInterface $entityManager, string $username): void
    {
        if (array_key_exists('AuditLog', $entityManager->getConfiguration()->getEntityNamespaces())) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();

        $entityUpdates = $unitOfWork->getScheduledEntityUpdates();
        $entityInsertions = $unitOfWork->getScheduledEntityInsertions();
        $entityDeletions = $unitOfWork->getScheduledEntityDeletions();

        // Not yet supported
        // $collectionChanges = array_merge($unitOfWork->getScheduledCollectionUpdates(), $unitOfWork->getScheduledCollectionDeletions());

        $changedEntities = [];
        $this->groupByClass($unitOfWork, $changedEntities, ChangeSet::TYPE_UPDATE, $entityUpdates);
        $this->groupByClass($unitOfWork, $changedEntities, ChangeSet::TYPE_INSERT, $entityInsertions);
        $this->groupByClass($unitOfWork, $changedEntities, ChangeSet::TYPE_DELETE, $entityDeletions);

        $auditLogEntries = [];

        foreach($changedEntities as $className => $changeSets) {
            foreach($this->entityLoggers as $entityLogger) {
                if ($entityLogger->supports($className)) {
                    $auditLogEntries = array_merge($auditLogEntries, $entityLogger->getAuditLogEntries($changeSets, $username));
                }
            }
        }

        foreach($auditLogEntries as $auditLogEntry) {
            $this->auditLogEntityManager->persist($auditLogEntry);
        }

        $this->auditLogEntityManager->flush();
    }

    protected function groupByClass(UnitOfWork $unitOfWork, array &$changedEntities, string $type, array $entities): void
    {
        foreach($entities as $entity) {
            $className = get_class($entity);
            $changeSet = [];

            if ($type !== ChangeSet::TYPE_DELETE) {
                $changeSet = $this->removeNonChanges($unitOfWork->getEntityChangeSet($entity));

                if (empty($changeSet)) {
                    continue;
                }
            }

            if (!isset($changedEntities[$className])) {
                $changedEntities[$className] = [];
            }

            $changedEntities[$className][] = new ChangeSet($entity, $type, $changeSet);
        }
    }

    protected function removeNonChanges(array $changes): array
    {
        foreach($changes as $key => $change) {
            $areIdentical = $change[0] === $change[1];
            $areEqualDateTimes = $change[0] instanceof DateTime && $change[1] instanceof DateTime && $change[0] == $change[1];

            if ($areIdentical || $areEqualDateTimes) {
                unset($changes[$key]);
            }
        }

        return $changes;
    }
}