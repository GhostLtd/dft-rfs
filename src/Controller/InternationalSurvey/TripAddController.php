<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Repository\International\VehicleRepository;
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
class TripAddController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_trip_add_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_trip_add_state';

    protected SurveyResponse $surveyResponse;
    protected ?Vehicle $vehicle = null;

    public function getSessionKey(): string
    {
        $routeParams = $this->requestStack->getCurrentRequest()->attributes->get('_route_params', []);
        $vehicleId = 'vehicle-' . $routeParams['vehicleId'];
        $class = static::class;
        return "wizard.{$class}.{$vehicleId}";
    }

    #[Route(path: '/international-survey/vehicles/{vehicleId}/add-trip/{state}', name: self::WIZARD_ROUTE)]
    #[Route(path: '/international-survey/vehicles/{vehicleId}/add-trip', name: self::START_ROUTE)]
    public function index(WorkflowInterface $internationalSurveyTripStateMachine,
                          VehicleRepository $vehicleRepository,
                          Request $request,
                          string $vehicleId,
                          ?string $state = null): Response
    {
        $this->surveyResponse = $this->getSurveyResponse();
        $this->vehicle = $vehicleRepository->findOneByIdAndSurveyResponse($vehicleId, $this->surveyResponse);

        if (!$this->vehicle) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($internationalSurveyTripStateMachine, $request, $state);
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new TripState());

        $this->setSubjectOnWizard($this->vehicle, $formWizard);

        return $formWizard;
    }

    #[\Override]
    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['vehicleId' => $this->vehicle->getId(), 'state' => $state]);
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        return $this->redirectToRoute(VehicleController::VEHICLE_ROUTE, ['vehicleId' => $this->vehicle->getId()]);
    }

    protected function setSubjectOnWizard(Vehicle $vehicle, FormWizardStateInterface $formWizard): void
    {
        $trip = $formWizard->getSubject() ?? new Trip();
        $trip->setVehicle($vehicle);
        $formWizard->setSubject($trip);
    }
}
