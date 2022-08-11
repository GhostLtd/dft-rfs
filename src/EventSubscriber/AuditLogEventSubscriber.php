<?php

namespace App\EventSubscriber;

use App\Utility\AuditLogger;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
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
        $objectManager = $eventArgs->getObjectManager();

        if ($objectManager instanceof EntityManagerInterface) {
            $this->auditLogger->log($objectManager);
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }
}
