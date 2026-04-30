<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Repository\International\VehicleRepository;
use App\Workflow\InternationalSurvey\TripState;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
#[Route(path: '/international-survey/vehicles/{vehicleId}/add-trip', name: 'app_internationalsurvey_trip_add_')]
class TripAddController extends AbstractSessionStateFormWizardController
{
    use SurveyHelperTrait;

    protected ?Vehicle $vehicle = null;

    public function __construct(
        FormWizardManager        $formWizardManager,
        protected RequestStack   $requestStack,
        PropertyMerger           $propertyMerger,
        protected VehicleRepository $vehicleRepository,
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    public function getSessionKey(): string
    {
        $routeParams = $this->requestStack->getCurrentRequest()->attributes->get('_route_params', []);
        $vehicleId = 'vehicle-' . $routeParams['vehicleId'];
        $class = static::class;
        return "wizard.{$class}.{$vehicleId}";
    }

    #[Route(path: '', name: 'start')]
    #[Route(path: '/{state}', name: 'wizard')]
    public function index(Request $request, string $vehicleId, ?string $state = null): Response
    {
        $this->vehicle = $this->vehicleRepository->findOneByIdAndSurveyResponse($vehicleId, $this->getSurveyResponse());

        if (!$this->vehicle) {
            throw new NotFoundHttpException();
        }

        if ($state === TripState::STATE_DISTANCE && !$this->vehicle->getSurveyResponse()->getSurvey()->isTripDistanceEnabled()) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($request, $state);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var TripState $state */
        $state = $this->session->get($this->getSessionKey(), new TripState());

        $trip = $state->getSubject() ?? new Trip();
        $trip->setVehicle($this->vehicle);
        $state->setSubject($trip);

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_internationalsurvey_trip_add_wizard', [
            'vehicleId' => $this->vehicle->getId(),
            'state' => (string) $place,
        ]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl(VehicleController::VEHICLE_ROUTE, ['vehicleId' => $this->vehicle->getId()]));
    }
}
