<?php

namespace App\Doctrine\Hydrators;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use PDO;

class ColumnHydrator extends AbstractHydrator
{
    #[\Override]
    protected function hydrateAllData(): array
    {
        return $this->stmt->fetchFirstColumn();
    }
}
