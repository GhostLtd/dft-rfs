<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Trip;
use App\Repository\International\TripRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\TripState;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class TripEditController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const WIZARD_ROUTE = 'app_internationalsurvey_trip_edit_state';

    /**
     * @var Trip
     */
    protected $trip;

    /**
     * @Route("/international-survey/trips/{tripId}/{state}", name=self::WIZARD_ROUTE)
     */
    public function index(WorkflowInterface $internationalSurveyTripStateMachine,
                          Request $request,
                          TripRepository $tripRepository,
                          UserInterface $user,
                          string $tripId,
                          string $state): Response
    {
        $surveyResponse = $this->getSurveyResponse($user);
        $this->trip = $tripRepository->findOneByIdAndSurveyResponse($tripId, $surveyResponse);

        if (!$this->trip) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($internationalSurveyTripStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var FormWizardInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new TripState());

        $trip = $formWizard->getSubject() ?? $this->trip;
        $this->trip->mergeTripChanges($trip);
        $formWizard->setSubject($this->trip);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['tripId' => $this->trip->getId(), 'state' => $state]);
    }
}
