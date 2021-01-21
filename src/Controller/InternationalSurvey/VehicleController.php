<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Vehicle;
use App\Form\Admin\InternationalSurvey\VehicleDeleteType;
use App\Repository\International\VehicleRepository;
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
class VehicleController extends AbstractController
{
    use SurveyHelperTrait;

    protected const ROUTE_PREFIX = 'app_internationalsurvey_vehicle_';

    public const DELETE_ROUTE = self::ROUTE_PREFIX.'delete';
    public const VEHICLE_ROUTE = self::ROUTE_PREFIX.'view';

    protected VehicleRepository $vehicleRepository;

    public function __construct(VehicleRepository $vehicleRepository)
    {
        $this->vehicleRepository = $vehicleRepository;
    }

    /**
     * @Route("/international-survey/vehicles/{vehicleId}", name=self::VEHICLE_ROUTE)
     */
    public function vehicle(UserInterface $user, string $vehicleId)
    {
        $vehicle = $this->getVehicle($user, $vehicleId);

        return $this->render('international_survey/vehicle/vehicle.html.twig', [
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * @Route("/international-survey/vehicles/{vehicleId}/delete", name=self::DELETE_ROUTE)
     */
    public function delete(UserInterface $user, string $vehicleId, Request $request, DeleteHelper $deleteHelper): Response
    {
        $vehicle = $this->getVehicle($user, $vehicleId);
        $form = $this->createForm(VehicleDeleteType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $delete = $form->get('delete');
            if ($delete instanceof SubmitButton && $delete->isClicked()) {
                $deleteHelper->deleteVehicle($vehicle);

                $this->addFlash('notification', new NotificationBanner('Success', "Vehicle successfully deleted", "The vehicle was deleted.", ['type' => 'success']));
                return new RedirectResponse($this->generateUrl(IndexController::SUMMARY_ROUTE));
            } else {
                $this->addFlash('notification', new NotificationBanner('Important', 'Vehicle not deleted', "The request to delete this vehicle was cancelled."));
                return new RedirectResponse($this->generateUrl(VehicleController::VEHICLE_ROUTE, ['vehicleId' => $vehicleId]));
            }
        }

        return $this->render('international_survey/vehicle/delete.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form->createView(),
        ]);
    }

    protected function getVehicle(UserInterface $user, string $vehicleId): Vehicle
    {
        if (!$response = $this->getSurveyResponse($user)) {
            throw new AccessDeniedHttpException();
        } else if (!$vehicle = $this->vehicleRepository->findOneByIdAndSurveyResponse($vehicleId, $response)) {
            throw new NotFoundHttpException();
        }

        return $vehicle;
    }
}