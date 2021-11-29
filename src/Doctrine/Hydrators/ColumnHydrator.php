<?php

namespace App\Doctrine\Hydrators;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use PDO;

class ColumnHydrator extends AbstractHydrator
{
    protected function hydrateAllData()
    {
        return $this->_stmt->fetchFirstColumn();
    }
}
