<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Trip;
use App\Repository\International\TripRepository;
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
class TripEditController extends AbstractSessionStateFormWizardController
{
    use SurveyHelperTrait;

    protected ?Trip $trip = null;

    public function __construct(
        FormWizardManager       $formWizardManager,
        protected RequestStack  $requestStack,
        PropertyMerger          $propertyMerger,
        protected TripRepository $tripRepository,
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    public function getSessionKey(): string
    {
        $routeParams = $this->requestStack->getCurrentRequest()->attributes->get('_route_params', []);
        $tripId = 'trip-' . $routeParams['tripId'];
        $class = static::class;
        return "wizard.{$class}.{$tripId}";
    }

    #[Route(path: '/international-survey/trips/{tripId}/edit/{state}', name: 'app_internationalsurvey_trip_edit_wizard')]
    public function index(Request $request, string $tripId, string $state): Response
    {
        $this->trip = $this->tripRepository->findOneByIdAndSurveyResponse($tripId, $this->getSurveyResponse());

        if (!$this->trip) {
            throw new NotFoundHttpException();
        }

        if ($state === TripState::STATE_DISTANCE && !$this->trip->isDistanceEnabled()) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($request, $state);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var TripState $state */
        $state = $this->session->get($this->getSessionKey(), new TripState());

        $trip = $state->getSubject() ?? $this->trip;
        $this->trip->mergeTripChanges($trip);
        $state->setSubject($this->trip);

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_internationalsurvey_trip_edit_wizard', [
            'tripId' => $this->trip->getId(),
            'state' => (string) $place,
        ]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl(TripController::TRIP_ROUTE, ['id' => $this->trip->getId()]));
    }
}
