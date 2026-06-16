<?php

namespace App\EventListener;

use App\Utility\AuditLogger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class AuditLogEventListener
{
    public function __construct(protected AuditLogger $auditLogger)
    {}

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $objectManager = $eventArgs->getObjectManager();

        $this->auditLogger->log($objectManager);
    }
}
