<?php

namespace App\Utility\Domestic;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Utility\AbstractDeleteHelper;

class DeleteHelper extends AbstractDeleteHelper
{
    public function deleteDayStop(DayStop $stop, bool $flush=true)
    {
        $this->entityManager->remove($stop);
        $flush && $this->entityManager->flush();
    }

    public function deleteDaySummary(DaySummary $summary, bool $flush=true)
    {
        $this->entityManager->remove($summary);
        $flush && $this->entityManager->flush();
    }
}