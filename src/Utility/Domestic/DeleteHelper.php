<?php

namespace App\Utility\Domestic;

use App\Entity\Domestic\DayStop;
use App\Utility\AbstractDeleteHelper;

class DeleteHelper extends AbstractDeleteHelper
{
    public function deleteDayStop(DayStop $stop, bool $flush=true)
    {
        $this->entityManager->remove($stop);
        $flush && $this->entityManager->flush();
    }
}