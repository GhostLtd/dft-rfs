<?php

namespace App\EventListener\DomesticSurvey;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Entity\Volume;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Proxy;

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
                if ($entity instanceof DayStop) {
                    $entity = $entity->getDay();
                }

                if ($entity instanceof DaySummary) {
                    $entity = $entity->getDay();
                }

                if ($entity instanceof Day) {
                    $entity = $entity->getResponse();
                }

                if ($entity instanceof Vehicle) {
                    $entity = $entity->getResponse();
                }

                if ($entity instanceof SurveyResponse) {
                    $entity = $entity->getSurvey();
                }

                if ($entity instanceof Survey) {
                    if ($entity instanceof Proxy && !$entity->__isInitialized()) {
                        // Doctrine's usual proxy initialisation was failing - possibly due to this survey proxy being
                        // from the session and lacking its initializer.

                        // If we have an uninitialized proxy, we therefore trigger the load so that the survey's data is
                        // correct.
                        $entity = $this->entityManager->find(Survey::class, $entity->getId());
                    }

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

    public function checkSurvey(Survey $survey): void
    {
        $response = $survey->getResponse();

        if (!$response) {
            // If there's no response, then there's nothing to remove
            return;
        }

        $isSoldScrappedStolenOrOnHire = $response->isSoldScrappedStolenOrOnHire();
        $possessionOfVehicle = $response->getIsInPossessionOfVehicle();
        $isExemptVehicle = $response->getIsExemptVehicleType();

        if (!$survey->shouldAskWhyUnfilled()) {
            $survey->setReasonForUnfilledSurvey(null);
        }

        if ($possessionOfVehicle === SurveyResponse::IN_POSSESSION_YES &&
            !$isExemptVehicle &&
            $response->hasJourneys()
        ) {
            // If we have journeys, then why would anyone have filled out reasons for having not filled the survey?
            $survey->getResponse()
                ->setReasonForEmptySurvey(null)
                ->setReasonForEmptySurveyOther(null);
        }

        if ($isExemptVehicle || $isSoldScrappedStolenOrOnHire) {
            // If the vehicle is exempt, or we're not-in-possession, then the survey can be closed at this point
            // without any further details.
            $this->deleteDriverAvailability($survey);
            $this->deleteDaysJourneysAndStages($response);
            $this->clearFinalDetails($response);
            $this->clearVehicleAndBusinessDetails($response);
        }

        $this->recomputeChangeSetFor($survey);
    }

    private function deleteDriverAvailability(Survey $survey): void
    {
        $driverAvailability = $survey->getDriverAvailability();
        if ($driverAvailability) {
            $this->entityManager->remove($driverAvailability);
            $survey->setDriverAvailability(null);
        }
    }

    private function clearFinalDetails(SurveyResponse $response): void
    {
        $response
            ->setReasonForEmptySurvey(null)
            ->setReasonForEmptySurveyOther(null);

        $this->recomputeChangeSetFor($response);
    }

    private function deleteDaysJourneysAndStages(SurveyResponse $response): void
    {
        foreach($response->getDays() as $day) {
            $response->removeDay($day);
            $this->entityManager->remove($day);
        }

        $this->recomputeChangeSetFor($response);
    }

    private function clearVehicleAndBusinessDetails(?SurveyResponse $response): void
    {
        $vehicle = $response->getVehicle();
        $response
            ->setNumberOfEmployees(null)
            ->setBusinessNature(null);

        $this->recomputeChangeSetFor($response);

        if ($vehicle) {
            // As per a note in admin/domestic/surveys/view.html.twig, vehicle gets created when initial-details
            // is complete, although at that stage it only contains regMark. Therefore, clearing vehicle details
            // means clearing the details rather than removing the vehicle entity itself (which would cause
            // problems - e.g. admin dashboard)
            $vehicle
                ->setAxleConfiguration(null)
                ->setBodyType(null)
                ->setCarryingCapacity(null)
                ->setFuelQuantity(new Volume())
                ->setGrossWeight(null)
                ->setOperationType(null)
                ->setTrailerConfiguration(null);

            $this->recomputeChangeSetFor($vehicle);
        }
    }

    public function recomputeChangeSetFor($entity): void
    {
        $metaData = $this->entityManager->getClassMetadata($entity::class);
        $this->uow->recomputeSingleEntityChangeSet($metaData, $entity);
    }
}
