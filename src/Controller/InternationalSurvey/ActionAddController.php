<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Entity\Utility;
use App\Repository\International\TripRepository;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\ActionState;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
#[Route(path: '/international-survey')]
class ActionAddController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    protected ?Trip $trip;

    public function getSessionKey(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        $routeParams = $request->attributes->get('_route_params', []);
        $tripId = $routeParams['tripId'] ?? null;
        $class = static::class;

        return "wizard.{$class}.trip-{$tripId}.add";
    }

    #[Route(path: '/trips/{tripId}/add-action/{state}', name: 'app_internationalsurvey_action_add_state', requirements: ['tripId' => Utility::UUID_REGEX])]
    #[Route(path: '/trips/{tripId}/add-action', name: 'app_internationalsurvey_action_add_start', requirements: ['tripId' => Utility::UUID_REGEX])]
    public function add(
        Request           $request,
        TripRepository    $tripRepository,
        WorkflowInterface $internationalSurveyActionStateMachine,
        string            $tripId,
        ?string           $state = null
    ): Response
    {
        $this->trip = $tripRepository->findByIdAndSurveyResponse($tripId, $this->getSurveyResponse());

        if (!$this->trip) {
            throw new NotFoundHttpException();
        }

        if (in_array($state, [ActionState::STATE_WEIGHT_UNLOADED, ActionState::STATE_CARGO_TYPE])
            && !$this->session->has($this->getSessionKey())
        ) {
            // final state of unloading or loading... but nothing in the session. Assume user navigated back from "add another"
            return $this->getCancelUrl();
        }

        $result = $this->doWorkflow($internationalSurveyActionStateMachine, $request, $state);
        if ($state === ActionState::STATE_ADD_ANOTHER) {
            $this->cleanUp();
        }
        return $result;
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        $actionRepository = $this->entityManager->getRepository(Action::class);

        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ActionState());

        $action = $formWizard->getSubject() ?? new Action();
        $action->setNumber($actionRepository->getNextNumber($this->trip->getId()));
        $action->setTrip($this->trip);

        $formWizard->setSubject($action);

        $subject = $formWizard->getSubject();
        $loadedAction = $subject->getLoadingAction();
        if ($loadedAction) {
            $subject->setLoadingAction($actionRepository->find($loadedAction->getId()));
        }

        return $formWizard;
    }

    #[\Override]
    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute('app_internationalsurvey_action_add_state', ['tripId' => $this->trip->getId(), 'state' => $state]);
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        return $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $this->trip->getId()]);
    }
}
