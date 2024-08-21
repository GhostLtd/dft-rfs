<?php

namespace App\Utility\AuditEntityLogger;

use App\Entity\AuditLog\AuditLog;
use DateTime;
use Doctrine\Common\Proxy\Proxy;

abstract class AbstractAuditEntityLogger implements AuditEntityLogger
{
    abstract function getCategory(): string;

    protected function getEntityId($entity): string
    {
        return (string) $entity->getId();
    }

    protected function createLog(string $username, object $entity, array $data): AuditLog
    {
        return (new AuditLog())
            ->setCategory($this->getCategory())
            ->setUsername($username)
            ->setEntityId($this->getEntityId($entity))
            ->setEntityClass($entity instanceof Proxy ? current(class_parents($entity)) : $entity::class)
            ->setTimestamp(new DateTime())
            ->setData($data);
    }

    protected function implementsInterface(string $className, string $interfaceName): bool
    {
        $interfaces = class_implements($className);
        return $interfaces && in_array($interfaceName, $interfaces);
    }
}