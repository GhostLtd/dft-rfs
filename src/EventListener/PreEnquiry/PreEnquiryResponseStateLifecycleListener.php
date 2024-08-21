<?php

namespace App\EventListener\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsDoctrineListener(event: Events::prePersist)]
class PreEnquiryResponseStateLifecycleListener
{
    public function __construct(protected WorkflowInterface $preEnquiryStateMachine)
    {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $response = $args->getObject();
        if (!$response instanceof PreEnquiryResponse) {
            return;
        }

        $preEnquiry = $response->getPreEnquiry();

        if ($this->preEnquiryStateMachine->can($preEnquiry, 'started')) {
            $this->preEnquiryStateMachine->apply($preEnquiry, 'started');
        }
    }
}
