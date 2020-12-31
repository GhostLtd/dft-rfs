<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Repository\International\ActionRepository;
use App\Repository\International\TripRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\ActionState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @Route("/international-survey")
 */
class ActionController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_action_add_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_action_add_state';
    public const ADD_ANOTHER_ROUTE = 'app_internationalsurvey_action_add_another';
    public const EDIT_ROUTE = 'app_internationalsurvey_action_edit_state';
    public const VIEW_ROUTE = 'app_internationalsurvey_action_view';

    private const MODE_EDIT = 'edit';
    private const MODE_ADD = 'add';

    protected $surveyResponse;

    /** @var Trip */
    protected $trip;

    /** @var Action */
    protected $action;

    /** @var string */
    protected $mode;

    protected $tripRepository;

    protected $actionRepository;

    public function __construct(ActionRepository $actionRepository, TripRepository $tripRepository, EntityManagerInterface $entityManager, SessionInterface $session)
    {
        parent::__construct($entityManager, $session);
        $this->actionRepository = $actionRepository;
        $this->tripRepository = $tripRepository;
    }

    /**
     * @Route("/trips/{tripId}/add-action/{state}", name=self::WIZARD_ROUTE)
     * @Route("/trips/{tripId}/add-action", name=self::START_ROUTE)
     */
    public function add(WorkflowInterface $internationalSurveyActionStateMachine, Request $request, UserInterface $user, string $tripId, ?string $state = null): Response
    {
        $this->loadSurveyAndTrip($user, $tripId);
        $this->mode = self::MODE_ADD;
        return $this->doWorkflow($internationalSurveyActionStateMachine, $request, $state);
    }

    /**
     * @Route("/trips/{tripId}/add-another-action", name=self::ADD_ANOTHER_ROUTE)
     */
    public function addAnother(UserInterface  $user, string $tripId): Response
    {
        $this->loadSurveyAndTrip($user, $tripId);
        $this->cleanUp();
        return $this->redirectToRoute(self::START_ROUTE, ['tripId' => $this->trip->getId()]);
    }

    /**
     * @Route("/actions/{actionId}", name=self::VIEW_ROUTE)
     */
    public function view(UserInterface $user, string $actionId) {
        $response = $this->getSurveyResponse($user);

        if (!$response) {
            throw new AccessDeniedHttpException();
        }

        $action = $this->actionRepository->findOneByIdAndSurveyResponse($actionId, $response);

        if (!$action) {
            throw new NotFoundHttpException();
        }

        return $this->render('international_survey/action/summary.html.twig', [
            'action' => $action,
        ]);
    }

    /**
     * @Route("/actions/{actionId}/edit/{state}", name=self::EDIT_ROUTE)
     */
    public function edit(WorkflowInterface $internationalSurveyActionStateMachine,
                          Request $request,
                          ActionRepository $actionRepository,
                          UserInterface $user,
                          string $actionId,
                          string $state): Response
    {
        $surveyResponse = $this->getSurveyResponse($user);
        $this->action = $actionRepository->findOneByIdAndSurveyResponse($actionId, $surveyResponse);
        $this->mode = self::MODE_EDIT;

        if (!$this->action) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($internationalSurveyActionStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var FormWizardInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ActionState());

        switch($this->mode) {
            case self::MODE_EDIT:
                $action = $formWizard->getSubject() ?? $this->action;
                $this->action->mergeActionChanges($action);
                $formWizard->setSubject($this->action);
                break;
            case self::MODE_ADD:
                $action = $formWizard->getSubject() ?? new Action();
                $action->setTrip($this->trip);
                $this->entityManager->persist($action);
                $formWizard->setSubject($action);
                break;
        }

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->mode === self::MODE_EDIT ?
            $this->redirectToRoute(self::EDIT_ROUTE, ['actionId' => $this->action->getId(), 'state' => $state]) :
            $this->redirectToRoute(self::WIZARD_ROUTE, ['tripId' => $this->trip->getId(), 'state' => $state]);
    }

    /** @param Action $subject */
    protected function preFlush($subject)
    {
        $loadedAction = $subject->getLoadingAction();

        if ($loadedAction) {
            $subject->setLoadingAction($this->actionRepository->find($loadedAction->getId()));
        }

        if ($this->mode === self::MODE_ADD) {
            $subject->setNumber($this->actionRepository->getNextNumber($this->trip->getId()));
        }
    }

    protected function loadSurveyAndTrip(UserInterface $user, string $tripId): void
    {
        $this->surveyResponse = $this->getSurveyResponse($user);
        $this->trip = $this->tripRepository->findByIdAndSurveyResponse($tripId, $this->surveyResponse);

        if (!$this->trip) {
            throw new NotFoundHttpException();
        }
    }
}
