<?php

namespace App\Controller\InternationalSurvey;

use App\Repository\International\TripRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class TripController extends AbstractController
{
    use SurveyHelperTrait;

    public const TRIP_ROUTE = 'app_internationalsurvey_trip_view';

    protected $tripRepository;

    public function __construct(TripRepository $tripRepository)
    {
        $this->tripRepository = $tripRepository;
    }

    /**
     * @Route("/international-survey/trips/{id}", name=self::TRIP_ROUTE)
     */
    public function trip(UserInterface $user, string $id) {
        $response = $this->getSurveyResponse($user);

        if (!$response) {
            throw new AccessDeniedHttpException();
        }

        $trip = $this->tripRepository->findOneByIdAndSurveyResponse($id, $response);

        if (!$trip) {
            throw new NotFoundHttpException();
        }

        return $this->render('international_survey/trip/trip.html.twig', [
            'trip' => $trip,
        ]);
    }
}