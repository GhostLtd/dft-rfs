<?php

namespace App\Utility\Domestic;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use App\Utility\AbstractDeleteHelper;

class DeleteHelper extends AbstractDeleteHelper
{
    public function deleteDayStop(DayStop $stop, bool $flush=true): void
    {
        $day = $stop->getDay();
        $this->entityManager->remove($stop);

        if ($day->getStops()->count() === 1) {
            // i.e. only this stop...
            $this->entityManager->remove($day);
        } else {
            $day->removeStop($stop, false);
        }

        $flush && $this->entityManager->flush();
    }

    public function deleteDaySummary(DaySummary $summary, bool $flush=true): void
    {
        $day = $summary->getDay();

        $this->entityManager->remove($summary);
        $this->entityManager->remove($day);

        $flush && $this->entityManager->flush();
    }

    public function deleteSurvey(Survey $survey, bool $flush=true): void
    {
        $response = $survey->getResponse();

        foreach($survey->getNotes() as $note) {
            $this->entityManager->remove($note);
        }

        if ($response) {
            foreach ($response->getDays() as $day) {
                if ($day->getHasMoreThanFiveStops()) {
                    foreach ($day->getStops() as $stop) {
                        $this->deleteDayStop($stop, false);
                    }
                } else {
                    $daySummary = $day->getSummary();
                    if ($daySummary) {
                        $this->deleteDaySummary($daySummary, false);
                    }
                }
            }

            $availability = $survey->getDriverAvailability();
            if ($availability) {
                $this->entityManager->remove($availability);
            }

            $this->entityManager->remove($response->getVehicle());
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
