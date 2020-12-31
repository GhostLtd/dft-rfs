<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Action;
use App\Repository\International\ActionRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\ActionState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class ActionEditController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const WIZARD_ROUTE = 'app_internationalsurvey_action_edit_state';

    /**
     * @var Action
     */
    protected $action;

    protected $actionRepository;

    public function __construct(ActionRepository $actionRepository, EntityManagerInterface $entityManager, SessionInterface $session)
    {
        parent::__construct($entityManager, $session);
        $this->actionRepository = $actionRepository;
    }

    /**
     * @Route("/international-survey/actions/{actionId}/edit/{state}", name=self::WIZARD_ROUTE)
     */
    public function index(WorkflowInterface $internationalSurveyActionStateMachine,
                          Request $request,
                          ActionRepository $actionRepository,
                          UserInterface $user,
                          string $actionId,
                          string $state): Response
    {
        $surveyResponse = $this->getSurveyResponse($user);
        $this->action = $actionRepository->findOneByIdAndSurveyResponse($actionId, $surveyResponse);

        if (!$this->action) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($internationalSurveyActionStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var FormWizardInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ActionState());

        $action = $formWizard->getSubject() ?? $this->action;
        $this->action->mergeActionChanges($action);
        $formWizard->setSubject($this->action);

        return $formWizard;
    }

    /** @param Action $subject */
    protected function preFlush($subject)
    {
        $loadedAction = $subject->getLoadingAction();

        if ($loadedAction) {
            $subject->setLoadingAction($this->actionRepository->find($loadedAction->getId()));
        }
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['actionId' => $this->action->getId(), 'state' => $state]);
    }
}
