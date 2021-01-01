<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Entity\Utility;
use App\Form\ConfirmationType;
use App\Form\InternationalSurvey\Action\DeleteActionType;
use App\Repository\International\ActionRepository;
use App\Repository\International\TripRepository;
use App\Utility\ReorderUtils;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\ActionState;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
 * @Security("!is_feature_enabled('IRHS_CONSIGNMENTS_AND_STOPS')")
 * @Security("is_granted('EDIT', user.getInternationalSurvey())")
 */
class ActionController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    private const ROUTE_PREFIX = 'app_internationalsurvey_action';

    public const START_ROUTE = self::ROUTE_PREFIX.'_add_start';
    public const WIZARD_ROUTE = self::ROUTE_PREFIX.'_add_state';
    public const ADD_ANOTHER_ROUTE = self::ROUTE_PREFIX.'_add_another';
    public const EDIT_ROUTE = self::ROUTE_PREFIX.'_edit_state';
    public const VIEW_ROUTE = self::ROUTE_PREFIX.'_view';
    public const DELETE_ROUTE = self::ROUTE_PREFIX.'_delete';
    public const REORDER_ROUTE = self::ROUTE_PREFIX.'_reorder';

    private const MODE_EDIT = 'edit';
    private const MODE_ADD = 'add';

    protected $surveyResponse;

    /** @var Trip */
    protected $trip;

    /** @var Action */
    protected $action;

    /** @var string */
    protected $mode;

    protected $actionRepository;

    protected $tripRepository;

    public function __construct(ActionRepository $actionRepository, TripRepository $tripRepository, EntityManagerInterface $entityManager, LoggerInterface $logger, SessionInterface $session)
    {
        parent::__construct($entityManager, $logger, $session);
        $this->actionRepository = $actionRepository;
        $this->tripRepository = $tripRepository;
    }

    /**
     * @Route("/trips/{tripId}/add-action/{state}", name=self::WIZARD_ROUTE, requirements={"tripId" = Utility::UUID_REGEX})
     * @Route("/trips/{tripId}/add-action", name=self::START_ROUTE, requirements={"tripId" = Utility::UUID_REGEX})
     */
    public function add(WorkflowInterface $internationalSurveyActionStateMachine, Request $request, UserInterface $user, string $tripId, ?string $state = null): Response
    {
        $this->loadSurveyAndTrip($user, $tripId);
        $this->mode = self::MODE_ADD;
        return $this->doWorkflow($internationalSurveyActionStateMachine, $request, $state);
    }

    /**
     * @Route("/trips/{tripId}/add-another-action", name=self::ADD_ANOTHER_ROUTE, requirements={"tripId" = Utility::UUID_REGEX})
     */
    public function addAnother(UserInterface  $user, string $tripId): Response
    {
        $this->loadSurveyAndTrip($user, $tripId);
        $this->cleanUp();
        return $this->redirectToRoute(self::START_ROUTE, ['tripId' => $this->trip->getId()]);
    }

    /**
     * @Route("/actions/{actionId}", name=self::VIEW_ROUTE, requirements={"actionId" = Utility::UUID_REGEX})
     */
    public function view(UserInterface $user, string $actionId) {
        $this->loadSurveyAndAction($user, $actionId);

        return $this->render('international_survey/action/view.html.twig', [
            'action' => $this->action,
        ]);
    }

    /**
     * @Route("/actions/{actionId}/delete", name=self::DELETE_ROUTE, requirements={"actionId" = Utility::UUID_REGEX})
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
     * @Route("/actions/{actionId}/edit/{state}", name=self::EDIT_ROUTE, requirements={"actionId" = Utility::UUID_REGEX})
     */
    public function edit(WorkflowInterface $internationalSurveyActionStateMachine, Request $request, UserInterface $user, string $actionId, string $state): Response
    {
        $this->loadSurveyAndAction($user, $actionId);
        $this->mode = self::MODE_EDIT;
        return $this->doWorkflow($internationalSurveyActionStateMachine, $request, $state);
    }

    /**
     * @Route("/trips/{tripId}/reorder-actions", name=self::REORDER_ROUTE)
     */
    public function reorder(UserInterface $user, Request $request, EntityManagerInterface $entityManager, string $tripId): Response
    {
        $this->loadSurveyAndTrip($user, $tripId);

        /** @var Action[] $actions */
        $actions = $this->trip->getActions()->toArray();

        $mappingParam = $request->query->get('mapping', null);

        /** @var Action[] $sortedActions */
        $sortedActions = ReorderUtils::getSortedItems($actions, $mappingParam);

        $mapping = array_map(function(Action $action) {
            return $action->getNumber();
        }, $sortedActions);

        foreach($mapping as $i => $newPosition) {
            $actions[$newPosition - 1]->setNumber($i + 1);
        }

        $unloadedBeforeLoaded = [];
        foreach($sortedActions as $action) {
            if (!$action->getLoading()) {
                if ($action->getLoadingAction()->getNumber() > $action->getNumber()) {
                    $unloadedBeforeLoaded[] = $action->getNumber();
                }
            }
        }

        $form = $this->createForm(ConfirmationType::class, null, [
            'yes_label' => 'international.action.re-order.save',
            'no_label' => 'common.actions.cancel',
            'yes_disabled' => !empty($unloadedBeforeLoaded),
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid() && empty($unloadedBeforeLoaded)) {
                $yes = $form->get('yes');

                if ($yes instanceof SubmitButton && $yes->isClicked()) {
                    $entityManager->flush();
                }

                return $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $tripId]);
            }
        }

        return $this->render('international_survey/action/re-order.html.twig', [
            'mapping' => $mapping,
            'trip' => $this->trip,
            'sortedActions' => $sortedActions,
            'form' => $form->createView(),
            'unloadedBeforeLoaded' => $unloadedBeforeLoaded,
        ]);
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
