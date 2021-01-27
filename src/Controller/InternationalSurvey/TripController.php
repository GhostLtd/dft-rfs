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
use Symfony\Component\Security\Core\User\UserInterface;
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
    public function trip(UserInterface $user, string $id) {
        return $this->render('international_survey/trip/trip.html.twig', [
            'trip' => $this->getTrip($user, $id),
        ]);
    }

    /**
     * @Route("/international-survey/trips/{tripId}/delete", name=self::DELETE_ROUTE)
     * @Template("international_survey/trip/delete.html.twig")
     */
    public function delete(DeleteTripConfirmAction $deleteTripConfirmAction, Request $request, $tripId)
    {
        $trip = $this->getTrip($this->getUser(), $tripId);
        $deleteTripConfirmAction->setSubject($trip);
        return $deleteTripConfirmAction->controller(
            $deleteTripConfirmAction,
            $request,
            function () use ($trip) {return $this->generateUrl(VehicleController::VEHICLE_ROUTE, ['vehicleId' => $trip->getVehicle()->getId()]);},
            function () use ($trip) {return $this->generateUrl(self::TRIP_ROUTE, ['id' => $trip->getId()]);},
        );
    }

/*
    public function delete(UserInterface $user, string $tripId, Request $request, DeleteHelper $deleteHelper): Response
    {
        $trip = $this->getTrip($user, $tripId);
        $form = $this->createForm(TripDeleteType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $delete = $form->get('delete');
            $translationPrefix = 'international.trip-delete.notification';
            if ($delete instanceof SubmitButton && $delete->isClicked()) {
                $vehicleId = $trip->getVehicle()->getId();
                $deleteHelper->deleteTrip($trip);

                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, $deleteHelper->getDeletedNotification($translationPrefix));
                return new RedirectResponse($this->generateUrl(VehicleController::VEHICLE_ROUTE, ['vehicleId' => $vehicleId]));
            } else {
                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, $deleteHelper->getCancelledNotification($translationPrefix));
                return new RedirectResponse($this->generateUrl(self::TRIP_ROUTE, ['id' => $trip->getId()]));
            }
        }

        return $this->render('international_survey/trip/delete.html.twig', [
            'trip' => $trip,
            'form' => $form->createView(),
        ]);
    }
*/

    protected function getTrip(UserInterface $user, string $id): Trip
    {
        if (!$response = $this->getSurveyResponse($user)) {
            throw new AccessDeniedHttpException();
        } else if (!$trip = $this->tripRepository->findOneByIdAndSurveyResponse($id, $response)) {
            throw new NotFoundHttpException();
        }
        return $trip;
    }
}