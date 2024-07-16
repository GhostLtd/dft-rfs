<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Trip;
use App\Repository\International\TripRepository;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\TripState;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
class TripEditController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const WIZARD_ROUTE = 'app_internationalsurvey_trip_edit_state';

    protected ?Trip $trip = null;

    public function getSessionKey(): string
    {
        $routeParams = $this->requestStack->getCurrentRequest()->attributes->get('_route_params', []);
        $tripId = 'trip-' . $routeParams['tripId'];
        $class = static::class;
        return "wizard.{$class}.{$tripId}";
    }

    #[Route(path: '/international-survey/trips/{tripId}/edit/{state}', name: self::WIZARD_ROUTE)]
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

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new TripState());

        $trip = $formWizard->getSubject() ?? $this->trip;
        $this->trip->mergeTripChanges($trip);
        $formWizard->setSubject($this->trip);

        return $formWizard;
    }

    #[\Override]
    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['tripId' => $this->trip->getId(), 'state' => $state]);
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        return $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $this->trip->getId()]);
    }
}
