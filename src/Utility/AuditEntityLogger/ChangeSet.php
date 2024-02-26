<?php

namespace App\Utility\AuditEntityLogger;

class ChangeSet
{
    public const TYPE_DELETE = 'delete';
    public const TYPE_INSERT = 'insert';
    public const TYPE_UPDATE = 'update';

    public function __construct(protected object $entity, protected string $type, protected array $changes = [])
    {
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function setEntity(object $entity): ChangeSet
    {
        $this->entity = $entity;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): ChangeSet
    {
        $this->type = $type;
        return $this;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function setChanges(array $changes): ChangeSet
    {
        $this->changes = $changes;
        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->changes[$name]);
    }

    public function get(string $name)
    {
        return $this->changes[$name];
    }
}