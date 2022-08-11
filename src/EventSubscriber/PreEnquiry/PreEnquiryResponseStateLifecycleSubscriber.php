<?php

namespace App\EventSubscriber\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as OrmEvents;
use Symfony\Component\Workflow\WorkflowInterface;

class PreEnquiryResponseStateLifecycleSubscriber implements EventSubscriber
{
    private WorkflowInterface $preEnquiryStateMachine;

    public function __construct(
        WorkflowInterface $preEnquiryStateMachine
    ) {
        $this->preEnquiryStateMachine = $preEnquiryStateMachine;
    }

    public function prePersist(LifecycleEventArgs $args)
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

    public function getSubscribedEvents(): array
    {
        return [
            OrmEvents::prePersist => 'prePersist',
        ];
    }
}