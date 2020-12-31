<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Form\InternationalSurvey\Action\DeleteActionType;
use App\Repository\International\ActionRepository;
use App\Repository\International\TripRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\ActionState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
    public const DELETE_ROUTE = 'app_internationalsurvey_action_delete';

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
        $this->loadSurveyAndAction($user, $actionId);

        return $this->render('international_survey/action/view.html.twig', [
            'action' => $this->action,
        ]);
    }

    /**
     * @Route("/actions/{actionId}/delete", name=self::DELETE_ROUTE)
     */
    public function delete(UserInterface $user, Request $request, string $actionId) {
        $this->loadSurveyAndAction($user, $actionId);
        $form = $this->createForm(DeleteActionType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $cancel = $form->get('cancel');
            $delete = $form->get('delete');

            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return $this->redirectToRoute(self::VIEW_ROUTE, ['actionId' => $actionId]);
            }

            if ($delete instanceof SubmitButton && $delete->isClicked()) {
                $trip = $this->action->getTrip();

                $actionsIdsToRemove = $this->action->getUnloadingActions()->map(function(Action $action) {
                    return $action->getId();
                })->toArray();

                foreach($trip->getActions() as $action) {
                    if (in_array($action->getId(), $actionsIdsToRemove)) {
                        $trip->removeAction($action);
                        $this->entityManager->remove($action);
                    }
                }

                $this->entityManager->remove($this->action);
                $trip->removeAction($this->action);
                $trip->renumberActions();

                $this->entityManager->flush();
                return $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $trip->getId()]);
            }
        }

        return $this->render('international_survey/action/delete.html.twig', [
            'action' => $this->action,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/actions/{actionId}/edit/{state}", name=self::EDIT_ROUTE)
     */
    public function edit(WorkflowInterface $internationalSurveyActionStateMachine, Request $request, UserInterface $user, string $actionId, string $state): Response
    {
        $this->loadSurveyAndAction($user, $actionId);
        $this->mode = self::MODE_EDIT;
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

    protected function loadSurveyAndAction(UserInterface $user, string $actionId): void
    {
        $surveyResponse = $this->getSurveyResponse($user);
        $this->action = $this->actionRepository->findOneByIdAndSurveyResponse($actionId, $surveyResponse);

        if (!$this->action) {
            throw new NotFoundHttpException();
        }
    }
}