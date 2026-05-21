<?php

namespace App\EventListener\DomesticSurvey;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\SurveyResponse;
use App\Utility\QualityCheck\QualityCheckEngine;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Recomputes the QualityCheck on the owning SurveyResponse whenever a Day/DayStop/DaySummary edit lands.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class QualityFlagRecomputeListener
{
    /** @var array<int, SurveyResponse> */
    private array $pendingResponses = [];
    private bool $recomputing = false;

    public function __construct(
        private readonly QualityCheckEngine $engine,
    ) {}

    public function onFlush(OnFlushEventArgs $event): void
    {
        if ($this->recomputing) {
            return;
        }
        $uow = $event->getObjectManager()->getUnitOfWork();
        foreach ([
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions(),
        ] as $entities) {
            foreach ($entities as $entity) {
                $response = $this->resolveResponse($entity);
                if ($response !== null) {
                    $this->pendingResponses[spl_object_id($response)] = $response;
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if ($this->pendingResponses === [] || $this->recomputing) {
            return;
        }
        $em = $event->getObjectManager();
        $responses = $this->pendingResponses;
        $this->pendingResponses = [];
        $this->recomputing = true;
        try {
            foreach ($responses as $response) {
                $previousSignature = $response->getQualityFlagSignature();
                $set = $this->engine->computeAndPersist($response);
                // An edit that genuinely changes the flags resets the review state back to unseen.
                if ($response->getQualityFlagsSeen() && $set->signature() !== $previousSignature) {
                    $response->setQualityFlagsSeen(false);
                }
            }
            $em->flush();
        } finally {
            $this->recomputing = false;
        }
    }

    private function resolveResponse(object $entity): ?SurveyResponse
    {
        if ($entity instanceof DayStop || $entity instanceof DaySummary) {
            $entity = $entity->getDay();
        }
        if ($entity instanceof Day) {
            return $entity->getResponse();
        }
        return null;
    }
}
