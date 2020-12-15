<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Stop;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\Utility;
use App\Form\AddAnotherType;
use App\Form\ConfirmationType;
use App\Form\InternationalSurvey\Stop\StopType;
use App\Repository\International\TripRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/international-survey/trips/{tripId}", requirements={"tripId" = Utility::UUID_REGEX})
 */
class StopController extends AbstractController
{
    use SurveyHelperTrait;

    private const SUMMARY_PREFIX = 'app_internationalsurvey_stops';
    public const SUMMARY_ROUTE = self::SUMMARY_PREFIX."_summary";
    public const ADD_ROUTE = self::SUMMARY_PREFIX."_add";
    public const EDIT_ROUTE = self::SUMMARY_PREFIX."_edit";
    public const REMOVE_ROUTE = self::SUMMARY_PREFIX."_remove";

    protected $tripRepository;

    public function __construct(TripRepository $tripRepository)
    {
        $this->tripRepository = $tripRepository;
    }

    /**
     * @Route("/stops", name=self::SUMMARY_ROUTE)
     */
    public function summary(UserInterface $user, Request $request, string $tripId): Response
    {
        $response = $this->getSurveyResponse($user);
        $trip = $this->getTrip($response, $tripId);

        $form = $this->createForm(AddAnotherType::class, null, [
            'another_label' => 'international.stop.summary.add-another.label',
            'another_help' => 'international.stop.summary.add-another.help',
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $another = $form->get('another')->getData();

                return $another ?
                    $this->redirectToRoute(self::ADD_ROUTE, ['tripId' => $tripId]) :
                    $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $tripId]);
            }
        }

        return $this->render('international_survey/stop/trip-stops.html.twig', [
            'trip' => $trip,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/stop-add", name=self::ADD_ROUTE)
     * @Route("/stops/{stopId}", name=self::EDIT_ROUTE, requirements={"stopId" = Utility::UUID_REGEX})
     */
    public function addStop(UserInterface $user, Request $request, EntityManagerInterface $entityManager, string $tripId, string $stopId = null): Response
    {
        $surveyResponse = $this->getSurveyResponse($user);
        $trip = $this->getTrip($surveyResponse, $tripId);
        $stop = $this->getStop($trip, $stopId);

        $form = $this->createForm(StopType::class, $stop);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $cancel = $form->get('cancel');

                if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                    return $this->redirectToRoute(self::SUMMARY_ROUTE, ['tripId' => $tripId]);
                }

                if (!$stopId) {
                    /** @var Stop $stop */
                    $stop = $form->getData();
                    $trip->addStop($stop);

                    $entityManager->persist($stop);
                }
                $entityManager->flush();

                return $this->redirectToRoute(self::SUMMARY_ROUTE, ['tripId' => $tripId]);
            }
        }

        return $this->render('international_survey/stop/form-add.html.twig', [
            'trip' => $trip,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/stops/{stopId}/remove", name=self::REMOVE_ROUTE, requirements={"stopId" = Utility::UUID_REGEX})
     */
    public function removeStop(UserInterface $user, Request $request, EntityManagerInterface $entityManager, string $tripId, string $stopId): Response
    {
        $surveyResponse = $this->getSurveyResponse($user);
        $trip = $this->getTrip($surveyResponse, $tripId);
        $stop = $this->getStop($trip, $stopId);

        $form = $this->createForm(ConfirmationType::class, null, [
            'yes_label' => 'international.stop.remove.yes',
            'no_label' => 'international.stop.remove.no',
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $yes = $form->get('yes');

                if ($yes instanceof SubmitButton && $yes->isClicked()) {
                    $trip->removeStop($stop);
                    $entityManager->flush();
                }

                return $this->redirectToRoute(self::SUMMARY_ROUTE, ['tripId' => $tripId]);
            }
        }

        return $this->render('international_survey/stop/form-remove.html.twig', [
            'trip' => $trip,
            'stop' => $stop,
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

    protected function getStop(Trip $trip, ?string $stopId): ?Stop
    {
        if ($stopId === null) {
            return null;
        }

        $filteredStops = $trip->getStops()->filter(function(Stop $stop) use ($stopId) {
            return $stop->getId() === $stopId;
        });

        if (!($stop = $filteredStops->first())) {
            throw new NotFoundHttpException();
        }

        return $stop;
    }
}