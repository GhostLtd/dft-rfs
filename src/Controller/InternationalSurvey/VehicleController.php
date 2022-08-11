<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Vehicle;
use App\Repository\International\VehicleRepository;
use App\Utility\ConfirmAction\International\DeleteVehicleConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

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
    public function vehicle(string $vehicleId)
    {
        $vehicle = $this->getVehicle($vehicleId);

        return $this->render('international_survey/vehicle/vehicle.html.twig', [
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * @Route("/international-survey/vehicles/{vehicleId}/delete", name=self::DELETE_ROUTE)
     * @Template("international_survey/vehicle/delete.html.twig")
     */
    public function delete(string $vehicleId, Request $request, DeleteVehicleConfirmAction $confirmAction)
    {
        $vehicle = $this->getVehicle($vehicleId);

        return $confirmAction
            ->setSubject($vehicle)
            ->setExtraViewData([
                'vehicle' => $vehicle,
            ])
            ->controller(
                $request,
                fn() => $this->generateUrl(IndexController::SUMMARY_ROUTE),
                function() use ($vehicleId) {
                    return $this->generateUrl(VehicleController::VEHICLE_ROUTE, ['vehicleId' => $vehicleId]);
                },
            );
    }

    protected function getVehicle(string $vehicleId): Vehicle
    {
        if (!$response = $this->getSurveyResponse()) {
            throw new AccessDeniedHttpException();
        } else if (!$vehicle = $this->vehicleRepository->findOneByIdAndSurveyResponse($vehicleId, $response)) {
            throw new NotFoundHttpException();
        }

        return $vehicle;
    }
}