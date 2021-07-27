<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Trip;
use App\Repository\International\TripRepository;
use App\Utility\ConfirmAction\International\DeleteTripConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('EDIT', user.getInternationalSurvey())")
 */
class TripController extends AbstractController
{
    use SurveyHelperTrait;

    public const ROUTE_PREFIX = 'app_internationalsurvey_trip_';

    public const DELETE_ROUTE = self::ROUTE_PREFIX.'delete';
    public const TRIP_ROUTE = self::ROUTE_PREFIX.'view';

    protected TranslatorInterface $translator;
    protected TripRepository $tripRepository;

    public function __construct(TranslatorInterface $translator, TripRepository $tripRepository)
    {
        $this->translator = $translator;
        $this->tripRepository = $tripRepository;
    }

    /**
     * @Route("/international-survey/trips/{id}", name=self::TRIP_ROUTE)
     */
    public function trip(string $id) {
        return $this->render('international_survey/trip/trip.html.twig', [
            'trip' => $this->getTrip($id),
        ]);
    }

    /**
     * @Route("/international-survey/trips/{tripId}/delete", name=self::DELETE_ROUTE)
     * @Template("international_survey/trip/delete.html.twig")
     */
    public function delete(DeleteTripConfirmAction $deleteTripConfirmAction, Request $request, $tripId)
    {
        $trip = $this->getTrip($tripId);
        $deleteTripConfirmAction->setSubject($trip);
        return $deleteTripConfirmAction->controller(
            $request,
            function () use ($trip) {return $this->generateUrl(VehicleController::VEHICLE_ROUTE, ['vehicleId' => $trip->getVehicle()->getId()]);},
            function () use ($trip) {return $this->generateUrl(self::TRIP_ROUTE, ['id' => $trip->getId()]);},
        );
    }

    protected function getTrip(string $id): Trip
    {
        if (!$response = $this->getSurveyResponse()) {
            throw new AccessDeniedHttpException();
        } else if (!$trip = $this->tripRepository->findOneByIdAndSurveyResponse($id, $response)) {
            throw new NotFoundHttpException();
        }
        return $trip;
    }
}