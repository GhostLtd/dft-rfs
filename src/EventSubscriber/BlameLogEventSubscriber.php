<?php


namespace App\EventSubscriber;


use App\Utility\BlameLogger;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class BlameLogEventSubscriber implements EventSubscriber
{
    private $blameLogger;

    public function __construct(BlameLogger $blameLogger)
    {
        $this->blameLogger = $blameLogger;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->blameLogger->log($eventArgs->getEntityManager());
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
        ];
    }

}