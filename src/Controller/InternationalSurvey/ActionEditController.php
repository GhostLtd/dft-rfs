<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Action;
use App\Entity\Utility;
use App\Repository\International\ActionRepository;
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
class ActionEditController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    protected ?Action $action;

    public function getSessionKey(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        $routeParams = $request->attributes->get('_route_params', []);
        $actionId = $routeParams['actionId'] ?? null;
        $class = static::class;

        return "wizard.{$class}.action-{$actionId}.edit";
    }

    #[Route(path: '/actions/{actionId}/edit/{state}', name: 'app_internationalsurvey_action_edit_state', requirements: ['actionId' => Utility::UUID_REGEX])]
    public function edit(
        ActionRepository  $actionRepository,
        Request           $request,
        WorkflowInterface $internationalSurveyActionStateMachine,
        string            $actionId,
        string            $state
    ): Response
    {
        $this->action = $actionRepository->findOneByIdAndSurveyResponse($actionId, $this->getSurveyResponse());

        if (!$this->action) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($internationalSurveyActionStateMachine, $request, $state);
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        $actionRepository = $this->entityManager->getRepository(Action::class);

        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ActionState());

        $action = $formWizard->getSubject();

        if ($action !== null) {
            $this->action->mergeActionChanges($action);
        }

        $formWizard->setSubject($this->action);

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
        return $this->redirectToRoute('app_internationalsurvey_action_edit_state', ['actionId' => $this->action->getId(), 'state' => $state]);
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        return $this->redirectToRoute('app_internationalsurvey_action_view', ['actionId' => $this->action->getId()]);
    }
}
