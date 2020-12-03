<?php

namespace App\Controller\InternationalSurvey;

use App\Repository\International\VehicleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class VehicleController extends AbstractController
{
    use SurveyHelperTrait;

    public const SUMMARY_ROUTE = 'app_internationalsurvey_vehicle_summary';
    public const VEHICLE_ROUTE = 'app_internationalsurvey_vehicle_vehicle';

    protected $vehicleRepository;

    public function __construct(VehicleRepository $vehicleRepository)
    {
        $this->vehicleRepository = $vehicleRepository;
    }

    /**
     * @Route("/international-survey/vehicles/{registrationMark}", name=self::VEHICLE_ROUTE)
     */
    public function vehicle(UserInterface $user, string $registrationMark) {
        $response = $this->getSurveyResponse($user);

        if (!$response) {
            throw new AccessDeniedHttpException();
        }

        $vehicle = $this->vehicleRepository->findOneBy(['registrationMark' => $registrationMark, 'surveyResponse' => $response]);

        if (!$vehicle) {
            throw new NotFoundHttpException();
        }

        return $this->render('international_survey/vehicle/vehicle.html.twig', [
            'vehicle' => $vehicle,
        ]);
    }
}