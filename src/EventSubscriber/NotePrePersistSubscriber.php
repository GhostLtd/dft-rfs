<?php


namespace App\EventSubscriber;

use App\Entity\NoteTrait;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as OrmEvents;
use Symfony\Component\Security\Core\Security;

class NotePrePersistSubscriber implements EventSubscriber
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!in_array(NoteTrait::class, class_uses($entity))) {
            return;
        }
        /** @var $entity NoteTrait */

        $entity->setCreatedAt(new \DateTime());
        $entity->setCreatedBy($this->security->getUser()->getUsername());
    }

    public function getSubscribedEvents()
    {
        return [
            OrmEvents::prePersist => 'prePersist',
        ];
    }
}