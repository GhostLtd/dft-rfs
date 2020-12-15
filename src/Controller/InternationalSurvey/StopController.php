<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Stop;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Form\InternationalSurvey\Stop\StopType;
use App\Repository\International\TripRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class StopController extends AbstractController
{
    use SurveyHelperTrait;

    public const SUMMARY_ROUTE = 'app_internationalsurvey_stops_summary';
    public const ADD_ROUTE = 'app_internationalsurvey_stops_add';

    protected $tripRepository;

    public function __construct(TripRepository $tripRepository)
    {
        $this->tripRepository = $tripRepository;
    }

    /**
     * @Route("/international-survey/trips/{tripId}/stops", name=self::SUMMARY_ROUTE)
     */
    public function summary(UserInterface $user, string $tripId): Response
    {
        $response = $this->getSurveyResponse($user);
        $trip = $this->getTrip($response, $tripId);

        return $this->render('international_survey/stop/trip-stops.html.twig', [
            'trip' => $trip,
        ]);
    }

    /**
     * @Route("/international-survey/trips/{tripId}/stop-add", name=self::ADD_ROUTE)
     */
    public function addStop(UserInterface $user, Request $request, EntityManagerInterface $entityManager, string $tripId): Response
    {
        $surveyResponse = $this->getSurveyResponse($user);
        $trip = $this->getTrip($surveyResponse, $tripId);

        $form = $this->createForm(StopType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                /** @var Stop $stop */
                $stop = $form->getData();
                $trip->addStop($stop);

                $entityManager->persist($stop);
                $entityManager->flush();

                return $this->redirectToRoute(self::SUMMARY_ROUTE, ['tripId' => $tripId]);
            }
        }

        return $this->render('international_survey/stop/form-add.html.twig', [
            'surveyResponse' => $surveyResponse,
            'trip' => $trip,
            'form' => $form->createView(),
        ]);
    }

    protected function getTrip(SurveyResponse $response, string $tripId): Trip
    {
        $trip = $this->tripRepository->findOneByIdAndSurveyResponse($tripId, $response);

        if (!$trip) {
            throw new NotFoundHttpException();
        }

        return $trip;
    }
}