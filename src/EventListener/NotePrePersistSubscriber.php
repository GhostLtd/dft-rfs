<?php

namespace App\EventListener;

use App\Entity\NoteTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist)]
class NotePrePersistSubscriber
{
    public function __construct(protected Security $security)
    {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!in_array(NoteTrait::class, class_uses($entity))) {
            return;
        }
        /** @var $entity NoteTrait */
        $entity->setCreatedAt(new \DateTime());
        $entity->setCreatedBy($this->security->getUser()->getUserIdentifier());
    }
}
