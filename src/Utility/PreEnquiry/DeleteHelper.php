<?php

namespace App\Utility\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Utility\AbstractDeleteHelper;

class DeleteHelper extends AbstractDeleteHelper
{
    public function deleteSurvey(PreEnquiry $preEnquiry, bool $flush=true): void
    {
        foreach($preEnquiry->getNotes() as $note) {
            $this->entityManager->remove($note);
        }

        $response = $preEnquiry->getResponse();

        if ($response) {
            $this->entityManager->remove($response);
        }

        $this->entityManager->remove($preEnquiry);
        $flush && $this->entityManager->flush();
    }
}
