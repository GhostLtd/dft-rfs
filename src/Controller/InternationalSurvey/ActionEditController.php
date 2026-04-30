<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Action;
use App\Entity\Utility;
use App\Repository\International\ActionRepository;
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
class ActionEditController extends AbstractSessionStateFormWizardController
{
    use SurveyHelperTrait;

    protected ?Action $action = null;

    public function __construct(
        FormWizardManager                $formWizardManager,
        protected RequestStack           $requestStack,
        PropertyMerger                   $propertyMerger,
        protected EntityManagerInterface $entityManager,
        protected ActionRepository       $actionRepository,
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    public function getSessionKey(): string
    {
        $routeParams = $this->requestStack->getCurrentRequest()->attributes->get('_route_params', []);
        $actionId = $routeParams['actionId'] ?? null;
        $class = static::class;
        return "wizard.{$class}.action-{$actionId}.edit";
    }

    #[Route(path: '/actions/{actionId}/edit/{state}', name: 'app_internationalsurvey_action_edit_wizard', requirements: ['actionId' => Utility::UUID_REGEX])]
    public function edit(Request $request, string $actionId, string $state): Response
    {
        $this->action = $this->actionRepository->findOneByIdAndSurveyResponse($actionId, $this->getSurveyResponse());

        if (!$this->action) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($request, $state);
    }

    protected function getState(): FormWizardStateInterface
    {
        $actionRepository = $this->entityManager->getRepository(Action::class);

        /** @var ActionState $state */
        $state = $this->session->get($this->getSessionKey(), new ActionState());

        $sessionAction = $state->getSubject();
        if ($sessionAction !== null) {
            $this->action->mergeActionChanges($sessionAction);
        }
        $state->setSubject($this->action);

        $loadedAction = $this->action->getLoadingAction();
        if ($loadedAction) {
            $this->action->setLoadingAction($actionRepository->find($loadedAction->getId()));
        }

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_internationalsurvey_action_edit_wizard', [
            'actionId' => $this->action->getId(),
            'state' => (string) $place,
        ]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_internationalsurvey_action_view', ['actionId' => $this->action->getId()]));
    }
}
