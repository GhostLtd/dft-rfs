<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Trip;
use App\Form\Admin\InternationalSurvey\TripDeleteType;
use App\Repository\International\TripRepository;
use App\Utility\International\DeleteHelper;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Security("is_granted('EDIT', user.getInternationalSurvey())")
 */
class TripController extends AbstractController
{
    use SurveyHelperTrait;

    public const ROUTE_PREFIX = 'app_internationalsurvey_trip_';

    public const DELETE_ROUTE = self::ROUTE_PREFIX.'delete';
    public const TRIP_ROUTE = self::ROUTE_PREFIX.'view';

    protected TripRepository $tripRepository;

    public function __construct(TripRepository $tripRepository)
    {
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
     */
    public function delete(UserInterface $user, string $tripId, Request $request, DeleteHelper $deleteHelper): Response
    {
        $trip = $this->getTrip($user, $tripId);
        $form = $this->createForm(TripDeleteType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $delete = $form->get('delete');
            if ($delete instanceof SubmitButton && $delete->isClicked()) {
                $vehicleId = $trip->getVehicle()->getId();
                $deleteHelper->deleteTrip($trip);

                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', "Trip successfully deleted", "The trip was deleted.", ['style' => NotificationBanner::STYLE_SUCCESS]));
                return new RedirectResponse($this->generateUrl(VehicleController::VEHICLE_ROUTE, ['vehicleId' => $vehicleId]));
            } else {
                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Important', 'Trip not deleted', "The request to delete this trip was cancelled."));
                return new RedirectResponse($this->generateUrl(self::TRIP_ROUTE, ['id' => $trip->getId()]));
            }
        }

        return $this->render('international_survey/trip/delete.html.twig', [
            'trip' => $trip,
            'form' => $form->createView(),
        ]);
    }

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