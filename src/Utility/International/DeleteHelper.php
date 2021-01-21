<?php

namespace App\Utility\International;

use App\Entity\International\Action;
use App\Entity\International\Survey;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteHelper
{
    protected EntityManagerInterface $entityManager;
    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    public function deleteAction(Action $action, bool $flush=true)
    {
        $trip = $action->getTrip();

        foreach ($action->getUnloadingActions() as $unloadingAction) {
            $trip->removeAction($unloadingAction);
            $this->entityManager->remove($unloadingAction);
        }
        $trip->removeAction($action);
        $trip->renumberActions();
        $this->entityManager->remove($action);
        $flush && $this->entityManager->flush();
    }

    public function deleteTrip(Trip $trip, bool $flush=true)
    {
        foreach ($trip->getActions()->filter(fn(Action $action) => $action->getLoading()) as $action) {
            foreach($action->getUnloadingActions() as $unloadingAction) {
                $this->entityManager->remove($unloadingAction);
            }
            $this->entityManager->remove($action);
        }
        $this->entityManager->remove($trip);
        $flush && $this->entityManager->flush();
    }

    public function deleteVehicle(Vehicle $vehicle, bool $flush=true)
    {
        foreach($vehicle->getTrips() as $trip) {
            $this->deleteTrip($trip, false);
        }
        $this->entityManager->remove($vehicle);
        $flush && $this->entityManager->flush();
    }

    public function deleteSurvey(Survey $survey, bool $flush=true)
    {
        $response = $survey->getResponse();

        if ($response) {
            foreach ($response->getVehicles() as $vehicle) {
                $this->deleteVehicle($vehicle, false);
            }
            $this->entityManager->remove($response);
        }

        $this->entityManager->remove($survey);
        $flush && $this->entityManager->flush();
    }

    public function getDeletedNotification(string $prefix): NotificationBanner
    {
        return new NotificationBanner(
            $this->translator->trans('common.notification.success'),
            $this->translator->trans("{$prefix}.deleted.heading"),
            $this->translator->trans("{$prefix}.deleted.content"),
            ['style' => NotificationBanner::STYLE_SUCCESS]
        );
    }

    public function getCancelledNotification(string $prefix): NotificationBanner
    {
        return new NotificationBanner(
            $this->translator->trans('common.notification.important'),
            $this->translator->trans("{$prefix}.cancelled.heading"),
            $this->translator->trans("{$prefix}.cancelled.content")
        );
    }
}