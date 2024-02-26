<?php

namespace App\EventListener\InternationalSurvey;

use App\Entity\International\Action;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

class DataConsistencyListener
{
    protected ?EntityManagerInterface $entityManager = null;
    protected ?UnitOfWork $uow = null;

    public function onFlush(OnFlushEventArgs $event): void
    {
        $this->entityManager = $event->getObjectManager();
        $this->uow = $this->entityManager->getUnitOfWork();

        $surveysToCheck = [];

        foreach ([
            $this->uow->getScheduledEntityInsertions(),
            $this->uow->getScheduledEntityUpdates(),
        ] as $entities) {
            foreach($entities as $entity) {
                // If any of these has been updated, figure out the underlying Survey, and then add it to the list
                // of surveys to check.
                if ($entity instanceof Action) {
                    $entity = $entity->getTrip();
                }

                if ($entity instanceof Trip) {
                    $entity = $entity->getVehicle();
                }

                if ($entity instanceof Vehicle) {
                    $entity = $entity->getSurveyResponse();
                }

                if ($entity instanceof SurveyResponse) {
                    $entity = $entity->getSurvey();
                }

                if ($entity instanceof InternationalSurvey) {
                    $surveysToCheck[spl_object_id($entity)] = $entity;
                }
            }
        }

        foreach($surveysToCheck as $survey) {
            $this->checkSurvey($survey);
        }

        $this->entityManager = null;
        $this->uow = null;
    }

    public function checkSurvey(InternationalSurvey $survey): void
    {
        $response = $survey->getResponse();

        if (!$response) {
            // If there's no response, then there's nothing to remove
            return;
        }

        if (!$survey->shouldAskWhyEmptySurvey()) {
            // If we have trips recorded, then why would we record a reason for having an empty survey?
            $survey
                ->setReasonForEmptySurvey(null)
                ->setReasonForEmptySurveyOther(null);
        }

        if ($response->isNoLongerActive()) {
            // If the company is no longer active (or carries out only domestic work), then we shouldn't be recording
            // trips data
            $this->deleteVehicles($response);
        }

        $this->recomputeChangeSetFor($survey);
    }

    private function deleteVehicles(SurveyResponse $response): void
    {
        foreach($response->getVehicles() as $vehicle) {
            $response->removeVehicle($vehicle);
            $this->entityManager->remove($vehicle);
        }

        $this->recomputeChangeSetFor($response);
    }

    public function recomputeChangeSetFor($entity): void
    {
        $metaData = $this->entityManager->getClassMetadata($entity::class);
        $this->uow->recomputeSingleEntityChangeSet($metaData, $entity);
    }
}
