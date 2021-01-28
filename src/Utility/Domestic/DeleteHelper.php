<?php

namespace App\Utility\Domestic;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Utility\AbstractDeleteHelper;

class DeleteHelper extends AbstractDeleteHelper
{
    public function deleteDayStop(DayStop $stop, bool $flush=true)
    {
        $day = $stop->getDay();
        $this->entityManager->remove($stop);

        if ($day->getStops()->count() === 1) {
            // i.e. only this stop...
            $this->entityManager->remove($day);
        }

        $flush && $this->entityManager->flush();
    }

    public function deleteDaySummary(DaySummary $summary, bool $flush=true)
    {
        $day = $summary->getDay();

        $this->entityManager->remove($summary);
        $this->entityManager->remove($day);

        $flush && $this->entityManager->flush();
    }
}