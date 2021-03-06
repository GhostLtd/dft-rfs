<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Action;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\Utility;
use App\Form\ConfirmationType;
use App\Form\InternationalSurvey\Action\DeleteActionType;
use App\Repository\International\ActionRepository;
use App\Repository\International\TripRepository;
use App\Utility\International\DeleteHelper;
use App\Utility\ReorderUtils;
use App\Workflow\FormWizardManager;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\ActionState;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @Route("/international-survey")
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

    protected const MODE_EDIT = 'edit';
    protected const MODE_ADD = 'add';

    protected SurveyResponse $surveyResponse;
    protected ?Trip $trip = null;
    protected ?Action $action = null;
    protected string $mode;

    protected ActionRepository $actionRepository;
    protected TripRepository $tripRepository;
    protected FormWizardManager $formWizardManager;

    public function __construct(FormWizardManager $formWizardManager, ActionRepository $actionRepository, TripRepository $tripRepository, EntityManagerInterface $entityManager, LoggerInterface $logger, SessionInterface $session)
    {
        parent::__construct($formWizardManager, $entityManager, $logger, $session);
        $this->actionRepository = $actionRepository;
        $this->tripRepository = $tripRepository;
        $this->formWizardManager = $formWizardManager;
    }

    /**
     * @Route("/trips/{tripId}/add-action/{state}", name=self::WIZARD_ROUTE, requirements={"tripId" = Utility::UUID_REGEX})
     * @Route("/trips/{tripId}/add-action", name=self::START_ROUTE, requirements={"tripId" = Utility::UUID_REGEX})
     */
    public function add(WorkflowInterface $internationalSurveyActionStateMachine, Request $request, string $tripId, ?string $state = null): Response
    {
        $this->loadSurveyAndTrip($tripId);
        $this->mode = self::MODE_ADD;

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

    /**
     * @Route("/trips/{tripId}/add-another-action", name=self::ADD_ANOTHER_ROUTE, requirements={"tripId" = Utility::UUID_REGEX})
     */
    public function addAnother(string $tripId): Response
    {
        return $this->redirectToRoute(self::START_ROUTE, ['tripId' => $tripId]);
    }

    /**
     * @Route("/actions/{actionId}", name=self::VIEW_ROUTE, requirements={"actionId" = Utility::UUID_REGEX})
     */
    public function view(string $actionId) {
        $this->loadSurveyAndAction($actionId);

        return $this->render('international_survey/action/view.html.twig', [
            'action' => $this->action,
        ]);
    }

    /**
     * @Route("/actions/{actionId}/delete", name=self::DELETE_ROUTE, requirements={"actionId" = Utility::UUID_REGEX})
     */
    public function delete(Request $request, string $actionId, DeleteHelper $deleteHelper) {
        $this->loadSurveyAndAction($actionId);
        $form = $this->createForm(DeleteActionType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $cancel = $form->get('cancel');
            $delete = $form->get('delete');

            $translationPrefix = 'international.action-delete.notification';

            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, $deleteHelper->getCancelledNotification($translationPrefix));
                return $this->redirectToRoute(self::VIEW_ROUTE, ['actionId' => $actionId]);
            }

            if ($delete instanceof SubmitButton && $delete->isClicked()) {
                $tripId = $this->action->getTrip()->getId();
                $deleteHelper->deleteAction($this->action);
                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, $deleteHelper->getDeletedNotification($translationPrefix));
                return $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $tripId]);
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
    public function edit(WorkflowInterface $internationalSurveyActionStateMachine, Request $request, string $actionId, string $state): Response
    {
        $this->loadSurveyAndAction($actionId);
        $this->mode = self::MODE_EDIT;
        return $this->doWorkflow($internationalSurveyActionStateMachine, $request, $state);
    }

    /**
     * @Route("/trips/{tripId}/reorder-actions", name=self::REORDER_ROUTE)
     */
    public function reorder( Request $request, string $tripId): Response
    {
        $this->loadSurveyAndTrip($tripId);

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

            $redirectResponse = $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $tripId]);
            if ($form->isSubmitted()) {
                $cancel = $form->get('no');
                if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                    return $redirectResponse;
                }

                if ($form->isValid() && empty($unloadedBeforeLoaded)) {
                    $this->entityManager->flush();
                    return $redirectResponse;
                }
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

    protected function getFormWizard(): FormWizardStateInterface
    {
        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ActionState());

        switch($this->mode) {
            case self::MODE_EDIT:
                $action = $formWizard->getSubject();

                if ($action !== null) {
                    $this->action->mergeActionChanges($action);
                }

                $formWizard->setSubject($this->action);
                break;
            case self::MODE_ADD:
                $action = $formWizard->getSubject() ?? new Action();
                $action->setNumber($this->actionRepository->getNextNumber($this->trip->getId()));
                $action->setTrip($this->trip);

                $formWizard->setSubject($action);
                break;
        }

        $subject = $formWizard->getSubject();
        $loadedAction = $subject->getLoadingAction();
        if ($loadedAction) {
            $subject->setLoadingAction($this->actionRepository->find($loadedAction->getId()));
        }

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->mode === self::MODE_EDIT ?
            $this->redirectToRoute(self::EDIT_ROUTE, ['actionId' => $this->action->getId(), 'state' => $state]) :
            $this->redirectToRoute(self::WIZARD_ROUTE, ['tripId' => $this->trip->getId(), 'state' => $state]);
    }

    protected function getCancelUrl(): ?Response
    {
        return ($this->action && $this->action->getId())
            ? $this->redirectToRoute(self::VIEW_ROUTE, ['actionId' => $this->action->getId()])
            : $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $this->trip->getId()]);
    }

    protected function loadSurveyAndTrip(string $tripId): void
    {
        $this->surveyResponse = $this->getSurveyResponse();
        $this->trip = $this->tripRepository->findByIdAndSurveyResponse($tripId, $this->surveyResponse);

        if (!$this->trip) {
            throw new NotFoundHttpException();
        }
    }

    protected function loadSurveyAndAction(string $actionId): void
    {
        $surveyResponse = $this->getSurveyResponse();
        $this->action = $this->actionRepository->findOneByIdAndSurveyResponse($actionId, $surveyResponse);

        if (!$this->action) {
            throw new NotFoundHttpException();
        }
    }
}
