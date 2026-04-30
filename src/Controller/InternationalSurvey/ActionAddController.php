<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Entity\Utility;
use App\Repository\International\TripRepository;
use App\Workflow\InternationalSurvey\ActionState;
use Doctrine\ORM\EntityManagerInterface;
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
#[Route(path: '/international-survey')]
class ActionAddController extends AbstractSessionStateFormWizardController
{
    use SurveyHelperTrait;

    protected ?Trip $trip = null;

    public function __construct(
        FormWizardManager                $formWizardManager,
        protected RequestStack           $requestStack,
        PropertyMerger                   $propertyMerger,
        protected EntityManagerInterface $entityManager,
        protected TripRepository         $tripRepository,
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    public function getSessionKey(): string
    {
        $routeParams = $this->requestStack->getCurrentRequest()->attributes->get('_route_params', []);
        $tripId = $routeParams['tripId'] ?? null;
        $class = static::class;
        return "wizard.{$class}.trip-{$tripId}.add";
    }

    #[Route(path: '/trips/{tripId}/add-action', name: 'app_internationalsurvey_action_add_start', requirements: ['tripId' => Utility::UUID_REGEX])]
    #[Route(path: '/trips/{tripId}/add-action/{state}', name: 'app_internationalsurvey_action_add_wizard', requirements: ['tripId' => Utility::UUID_REGEX])]
    public function add(Request $request, string $tripId, ?string $state = null): Response
    {
        $this->trip = $this->tripRepository->findByIdAndSurveyResponse($tripId, $this->getSurveyResponse());

        if (!$this->trip) {
            throw new NotFoundHttpException();
        }

        if (in_array($state, [ActionState::STATE_WEIGHT_UNLOADED, ActionState::STATE_CARGO_TYPE])
            && !$this->session->has($this->getSessionKey())
        ) {
            // final state of unloading or loading... but nothing in the session. Assume user navigated back from "add another"
            return $this->getCancelRedirectResponse();
        }

        $result = $this->doWorkflow($request, $state);
        if ($state === ActionState::STATE_ADD_ANOTHER) {
            $this->cleanUp();
        }
        return $result;
    }

    protected function getState(): FormWizardStateInterface
    {
        $actionRepository = $this->entityManager->getRepository(Action::class);

        /** @var ActionState $state */
        $state = $this->session->get($this->getSessionKey(), new ActionState());

        $action = $state->getSubject() ?? new Action();
        $action->setNumber($actionRepository->getNextNumber($this->trip->getId()));
        $action->setTrip($this->trip);
        $state->setSubject($action);

        $loadedAction = $action->getLoadingAction();
        if ($loadedAction) {
            $action->setLoadingAction($actionRepository->find($loadedAction->getId()));
        }

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_internationalsurvey_action_add_wizard', [
            'tripId' => $this->trip->getId(),
            'state' => (string) $place,
        ]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl(TripController::TRIP_ROUTE, ['id' => $this->trip->getId()]));
    }
}
