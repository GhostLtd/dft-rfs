<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Vehicle;
use App\Repository\International\VehicleRepository;
use App\Utility\ConfirmAction\International\DeleteVehicleConfirmAction;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
class VehicleController extends AbstractController
{
    use SurveyHelperTrait;

    protected const ROUTE_PREFIX = 'app_internationalsurvey_vehicle_';

    public const DELETE_ROUTE = self::ROUTE_PREFIX.'delete';
    public const VEHICLE_ROUTE = self::ROUTE_PREFIX.'view';

    public function __construct(protected VehicleRepository $vehicleRepository)
    {
    }

    #[Route(path: '/international-survey/vehicles/{vehicleId}', name: self::VEHICLE_ROUTE)]
    public function vehicle(string $vehicleId): Response
    {
        $vehicle = $this->getVehicle($vehicleId);

        return $this->render('international_survey/vehicle/vehicle.html.twig', [
            'vehicle' => $vehicle,
        ]);
    }

    #[Route(path: '/international-survey/vehicles/{vehicleId}/delete', name: self::DELETE_ROUTE)]
    #[Template('international_survey/vehicle/delete.html.twig')]
    public function delete(string $vehicleId, Request $request, DeleteVehicleConfirmAction $confirmAction): RedirectResponse|array
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
                fn() => $this->generateUrl(VehicleController::VEHICLE_ROUTE, ['vehicleId' => $vehicleId]),
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
