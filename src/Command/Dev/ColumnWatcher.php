<?php

namespace App\Command\Dev;

class ColumnWatcher
{
    protected array $columnsSeen;

    public function __construct(protected array $columnNames)
    {
        $this->columnsSeen = [];
    }

    public function clearColumnsSeen(): void
    {
        $this->columnsSeen = [];
    }

    public function columnSeen(string $name): static
    {
        $this->columnsSeen[] = $name;
        return $this;
    }

    public function getUnseenColumns(): ?string
    {
        $unseenColumns = [];

        foreach($this->columnNames as $name) {
            if (!in_array($name, $this->columnsSeen)) {
                $unseenColumns[] = $name;
            }
        }

        return empty($unseenColumns) ? null : join(', ', $unseenColumns);
    }
}
