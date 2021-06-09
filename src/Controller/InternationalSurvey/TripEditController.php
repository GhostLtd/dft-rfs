<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Trip;
use App\Repository\International\TripRepository;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\TripState;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @Security("is_granted('EDIT', user.getInternationalSurvey())")
 */
class TripEditController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const WIZARD_ROUTE = 'app_internationalsurvey_trip_edit_state';

    protected ?Trip $trip;

    /**
     * @Route("/international-survey/trips/{tripId}/edit/{state}", name=self::WIZARD_ROUTE)
     */
    public function index(WorkflowInterface $internationalSurveyTripStateMachine,
                          Request $request,
                          TripRepository $tripRepository,
                          string $tripId,
                          string $state): Response
    {
        $surveyResponse = $this->getSurveyResponse();
        $this->trip = $tripRepository->findOneByIdAndSurveyResponse($tripId, $surveyResponse);

        if (!$this->trip) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($internationalSurveyTripStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardStateInterface
    {
        /** @var FormWizardStateInterface $formWizard */
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

    protected function getCancelUrl(): ?Response
    {
        return $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $this->trip->getId()]);
    }
}
