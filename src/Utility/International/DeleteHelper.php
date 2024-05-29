<?php

namespace App\Utility\International;

use App\Entity\International\Survey;
use App\Utility\AbstractDeleteHelper;

class DeleteHelper extends AbstractDeleteHelper
{
    public function deleteSurvey(Survey $survey, bool $flush=true): void
    {
        $response = $survey->getResponse();

        foreach($survey->getNotes() as $note) {
            $this->entityManager->remove($note);
        }

        if ($response) {
            foreach($response->getVehicles() as $vehicle) {
                $this->entityManager->remove($vehicle);
            }

            $this->entityManager->remove($response);
        }

        $this->entityManager->remove($survey);

        $user = $survey->getPasscodeUser();
        if ($user) {
            $this->entityManager->remove($user);
        }

        $flush && $this->entityManager->flush();
    }
}
