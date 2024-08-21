<?php

namespace App\Utility\RoRo;

use App\Entity\RoRo\Survey;
use App\Utility\AbstractDeleteHelper;

class DeleteHelper extends AbstractDeleteHelper
{
    public function deleteSurvey(Survey $survey, bool $flush=true): void
    {
        foreach($survey->getNotes() as $note) {
            $this->entityManager->remove($note);
        }

        $this->entityManager->remove($survey);
        $flush && $this->entityManager->flush();
    }
}
