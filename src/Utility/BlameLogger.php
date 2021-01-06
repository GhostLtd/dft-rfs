<?php


namespace App\Utility;


use App\Entity\BlameLog\BlameLog;
use App\Entity\BlameLoggable;
use App\Entity\PasscodeUser;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class BlameLogger
{
    private $inserts = [];
    private $updates = [];
    private $deletions = [];

    private $blameLogEntityManager;
    private $security;
    private $log;
    private $appEnvironment;

    public function __construct(EntityManagerInterface $blameLogEntityManager, Security $security, LoggerInterface $log, $appEnvironment)
    {
        $this->blameLogEntityManager = $blameLogEntityManager;
        $this->security = $security;
        $this->log = $log;
        $this->appEnvironment = $appEnvironment;
    }

    public function log(EntityManagerInterface $entityManager)
    {
        try {
            $this->createLogs($entityManager);
            $this->saveLogs();
        } catch (\Throwable $e) {
            $this->log->error(sprintf("[BlameLogger] %s: %s (%s Line %s)", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
            if ($this->appEnvironment === 'dev') {
                throw $e;
            }
        }
    }

    private function createLogs(EntityManagerInterface $entityManager)
    {
        if (array_key_exists('BlameLog', $entityManager->getConfiguration()->getEntityNamespaces())) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();

        $entityUpdates = $unitOfWork->getScheduledEntityUpdates();
        $entityInsertions = $unitOfWork->getScheduledEntityInsertions();
        $entityDeletions = $unitOfWork->getScheduledEntityDeletions();

        $collectionChanges = array_merge($unitOfWork->getScheduledCollectionUpdates(), $unitOfWork->getScheduledCollectionDeletions());

        // Insertions
        // ----------------------------------------------------------------------------------------------------
        foreach($entityInsertions as $entity) {
            if ($entity instanceof BlameLoggable) {
                $this->inserts[] = [
                    'entity' => $entity,
                    'properties' => $unitOfWork->getEntityChangeSet($entity),
                ];
            }
        }

        // Deletions
        // ----------------------------------------------------------------------------------------------------
        foreach($entityDeletions as $entity) {
            if ($entity instanceof BlameLoggable) {
                $this->deletions[] = $entity;
            }
        }

        // Updates
        // ----------------------------------------------------------------------------------------------------

        // Collection changes (e.g. M2M intermediate tables)
        foreach($collectionChanges as $change) {
            if ($change instanceof PersistentCollection) {
                $entity = $change->getOwner();
                $mapping = $change->getMapping();

                if ($entity instanceof BlameLoggable) {
                    // Only worth logging if the entity is being updated...
                    if (in_array($entity, $entityUpdates)) {
                        $this->addUpdate($entity, [$mapping['fieldName']]);
                    }
                }
            } else {
                throw new \RuntimeException('Not a PersistentCollection');
            }
        }

        // Direct entity updates
        foreach($entityUpdates as $entity) {
            if ($entity instanceof BlameLoggable) {
                $changeSet = $unitOfWork->getEntityChangeSet($entity);
                $this->addUpdate($entity, $changeSet);
            }
        }
    }

    // Filter the change set - remove unchanged properties
    protected function filterChangeSet(array $changeSet)
    {
        foreach ($changeSet as $key=>$change) {
            if ($change[0] === $change[1]) {
                // no change on this property, don't need to log it
                unset($changeSet[$key]);
            } else {
                // there was a change
                $changeSet[$key][0] = $this->filterChange($change[0]);
                $changeSet[$key][1] = $this->filterChange($change[1]);
            }
        }
        return $changeSet;
    }

    // Filter a specific change item - de-reference the id, or normalize
    protected function filterChange($change)
    {
        if ($change instanceof BlameLoggable) {
            $change = $change->getId();
        } else if (is_object($change) && strpos(get_class($change), "App\\Entity\\") === 0) {
            $norm = new ObjectNormalizer();
            $change = $norm->normalize($change);
        }
        return $change;
    }

    protected function addUpdate(BlameLoggable $entity, $changeSet)
    {
        $key = $entity->getId().':'.get_class($entity);

        if (!isset($this->updates[$key])) {
            $this->updates[$key] = [
                'entity' => $entity,
                'properties' => []
            ];
        }

        $updatedProperties = $this->updates[$key]['properties'];

        foreach($changeSet as $propertyName => $propertyChange) {
            if (!in_array($propertyName, $updatedProperties)) {
                $updatedProperties[$propertyName] = $propertyChange;
            }
        }

        $this->updates[$key]['properties'] = $updatedProperties;
    }

    public function saveLogs()
    {
        /** @var BlameLog[] $logs */
        $logs = [];

        foreach ($this->inserts as $data) {
            $logs[] = $this->createBlameLog($data['entity'], BlameLog::TYPE_INSERT, $this->filterChangeSet($data['properties']));
        }

        foreach ($this->deletions as $entity) {
            $logs[] = $this->createBlameLog($entity, BlameLog::TYPE_DELETE);
        }

        foreach ($this->updates as $data) {
            /** @var BlameLoggable $entity */
            $logs[] = $this->createBlameLog($data['entity'], BlameLog::TYPE_UPDATE, $this->filterChangeSet($data['properties']));
        }

        $this->inserts = [];
        $this->updates = [];
        $this->deletions = [];

        if (!empty($logs)) {
            /** @var UserInterface | null $user */
            $user = $this->security->getToken() ? $this->security->getUser() : null;

            $userId = $user
                ? ($user instanceof PasscodeUser)
                    ? 'survey-user'
                    : $user->getUsername()
                : 'unknown'
            ;

            foreach ($logs as $log) {
                $log->setUserId($userId);
                $this->blameLogEntityManager->persist($log);
            }
            $this->blameLogEntityManager->flush();
        }
    }

    protected function createBlameLog(BlameLoggable $entity, $type, $properties=null)
    {
        return (new BlameLog())
            ->setClass($entity instanceof Proxy ? current(class_parents($entity)) : get_class($entity))
            ->setType($type)
            ->setProperties($properties)
            ->setEntityId($entity->getId())
            ->setDescription($entity->getBlameLogLabel())
            ->setAssociatedEntity($entity->getAssociatedEntityClass())
            ->setAssociatedId($entity->getAssociatedEntityId())
            ;
    }
}
