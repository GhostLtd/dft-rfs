<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Vehicle;
use App\Repository\International\VehicleRepository;
use App\Security\Voter\International\VehicleChangeTrailerConfigVoter;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\VehicleState;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
class VehicleEditController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const WIZARD_ROUTE = 'app_internationalsurvey_vehicle_edit_state';

    protected ?Vehicle $vehicle = null;

    public function getSessionKey(): string
    {
        $routeParams = $this->requestStack->getCurrentRequest()->attributes->get('_route_params', []);
        $vehicleId = 'vehicle-' . $routeParams['vehicleId'];
        $class = static::class;
        return "wizard.{$class}.{$vehicleId}";
    }

    #[Route(path: '/international-survey/vehicles/{vehicleId}/{state}', name: self::WIZARD_ROUTE)]
    public function index(WorkflowInterface $internationalSurveyVehicleStateMachine,
                          Request $request,
                          VehicleRepository $vehicleRepository,
                          TranslatorInterface $translator,
                          string $vehicleId,
                          string $state): Response
    {
        $surveyResponse = $this->getSurveyResponse();
        $this->vehicle = $vehicleRepository->findOneByIdAndSurveyResponse($vehicleId, $surveyResponse);

        if (!$this->vehicle) {
            throw new NotFoundHttpException();
        }

        // check we can edit trailer/body config
        if (in_array($state, [VehicleState::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION, VehicleState::STATE_CHANGE_VEHICLE_BODY, VehicleState::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION])
            && !$this->isGranted(VehicleChangeTrailerConfigVoter::EDIT_TRAILER_CONFIGS, $this->vehicle)) {
            $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
                $translator->trans('international.vehicle-cant-edit-trailer-configs.title'),
                $translator->trans('international.vehicle-cant-edit-trailer-configs.heading'),
                $translator->trans('international.vehicle-cant-edit-trailer-configs.content')
            ));
            return $this->getCancelUrl();
        }

        return $this->doWorkflow($internationalSurveyVehicleStateMachine, $request, $state);
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new VehicleState());

        $vehicle = $formWizard->getSubject() ?? $this->vehicle;
        $this->vehicle->mergeVehicleChanges($vehicle);
        $formWizard->setSubject($this->vehicle);

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
}
