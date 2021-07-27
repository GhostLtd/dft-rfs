<?php

namespace App\EventSubscriber;

use App\Utility\AuditLogger;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class AuditLogEventSubscriber implements EventSubscriber
{
    private AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->auditLogger->log($eventArgs->getEntityManager());
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
        ];
    }
}
