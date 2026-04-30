<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Vehicle;
use App\Repository\International\VehicleRepository;
use App\Security\Voter\International\VehicleChangeTrailerConfigVoter;
use App\Workflow\InternationalSurvey\VehicleState;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
class VehicleEditController extends AbstractSessionStateFormWizardController
{
    use SurveyHelperTrait;

    protected ?Vehicle $vehicle = null;

    public function __construct(
        FormWizardManager           $formWizardManager,
        protected RequestStack      $requestStack,
        PropertyMerger              $propertyMerger,
        protected VehicleRepository $vehicleRepository,
        protected TranslatorInterface $translator,
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

    #[Route(path: '/international-survey/vehicles/{vehicleId}/{state}', name: 'app_internationalsurvey_vehicle_edit_wizard')]
    public function index(Request $request, string $vehicleId, string $state): Response
    {
        $this->vehicle = $this->vehicleRepository->findOneByIdAndSurveyResponse($vehicleId, $this->getSurveyResponse());

        if (!$this->vehicle) {
            throw new NotFoundHttpException();
        }

        // check we can edit trailer/body config
        if (in_array($state, [VehicleState::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION, VehicleState::STATE_CHANGE_VEHICLE_BODY,
                VehicleState::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION])
            && !$this->isGranted(VehicleChangeTrailerConfigVoter::EDIT_TRAILER_CONFIGS, $this->vehicle)) {
            $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
                $this->translator->trans('international.vehicle-cant-edit-trailer-configs.title'),
                $this->translator->trans('international.vehicle-cant-edit-trailer-configs.heading'),
                $this->translator->trans('international.vehicle-cant-edit-trailer-configs.content')
            ));
            return $this->getCancelRedirectResponse();
        }

        return $this->doWorkflow($request, $state);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var VehicleState $state */
        $state = $this->session->get($this->getSessionKey(), new VehicleState());

        $vehicle = $state->getSubject() ?? $this->vehicle;
        $this->vehicle->mergeVehicleChanges($vehicle);
        $state->setSubject($this->vehicle);

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_internationalsurvey_vehicle_edit_wizard', [
            'vehicleId' => $this->vehicle->getId(),
            'state' => (string) $place,
        ]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl(VehicleController::VEHICLE_ROUTE, ['vehicleId' => $this->vehicle->getId()]));
    }
}
