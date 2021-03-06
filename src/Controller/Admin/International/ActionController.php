<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Form\Admin\InternationalSurvey\ActionDeleteType;
use App\Form\Admin\InternationalSurvey\ActionLoadType;
use App\Form\Admin\InternationalSurvey\ActionUnloadType;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\International\DeleteHelper;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs")
 */
class ActionController extends AbstractController
{
    private const ROUTE_PREFIX = "admin_international_action_";

    public const ADD_LOADING_ROUTE = self::ROUTE_PREFIX . "add_loading";
    public const ADD_UNLOADING_ROUTE = self::ROUTE_PREFIX . "add_unloading";
    public const DELETE_ROUTE = self::ROUTE_PREFIX . "delete";
    public const EDIT_ROUTE = self::ROUTE_PREFIX . "edit";
    public const REORDER_ROUTE = self::ROUTE_PREFIX . "reorder";

    protected EntityManagerInterface $entityManager;
    protected RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/trip/{tripId}/add-loading-action", name=self::ADD_LOADING_ROUTE)
     * @Entity("trip", expr="repository.find(tripId)")
     */
    public function addLoadingAction(Trip $trip): Response
    {
        $action = (new Action())->setLoading(true);
        $trip->addAction($action);
        $this->entityManager->persist($action);

        return $this->handleRequest($action, 'admin/international/action/add-loading.html.twig', ['placeholders' => true]);
    }

    /**
     * @Route("/trip/{tripId}/add-unloading-action", name=self::ADD_UNLOADING_ROUTE)
     * @Entity("trip", expr="repository.find(tripId)")
     */
    public function addUnloadingAction(Trip $trip): Response
    {
        $action = (new Action())->setLoading(false);
        $trip->addAction($action);
        $this->entityManager->persist($action);

        return $this->handleRequest($action, 'admin/international/action/add-unloading.html.twig');
    }

    /**
     * @Route("/action/{actionId}/edit", name=self::EDIT_ROUTE)
     * @Entity("action", expr="repository.find(actionId)")
     */
    public function edit(Action $action): Response
    {
        $template = $action->getLoading() ?
            'admin/international/action/edit-loading.html.twig' :
            'admin/international/action/edit-unloading.html.twig';

        return $this->handleRequest($action, $template);
    }

    protected function handleRequest(Action $action, string $template, array $formOptions = []): Response
    {
        $survey = $action->getTrip()->getVehicle()->getSurveyResponse()->getSurvey();
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $survey);

        $formClass = $action->getLoading() ? ActionLoadType::class : ActionUnloadType::class;
        $form = $this->createForm($formClass, $action, $formOptions);
        $request = $this->requestStack->getCurrentRequest();

        $unmodifiedAction = clone $action;

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $surveyId = $survey->getId();
            $redirectResponse = new RedirectResponse(
                $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $surveyId]) .
                "#actions-{$action->getTrip()->getId()}"
            );

            $cancel = $form->get('cancel');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return $redirectResponse;
            };

            if ($form->isValid()) {
                $this->entityManager->flush();
                return $redirectResponse;
            }
        }

        return $this->render($template, [
            'action' => $unmodifiedAction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/action/{actionId}/delete", name=self::DELETE_ROUTE)
     * @Entity("action", expr="repository.findOneByIdWithRelatedActions(actionId)")
     */
    public function delete(Action $action, Request $request, DeleteHelper $deleteHelper): Response
    {
        $form = $this->createForm(ActionDeleteType::class);

        $trip = $action->getTrip();
        $survey = $trip->getVehicle()->getSurveyResponse()->getSurvey();
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $survey);

        $redirectUrl = $this->generateUrl(
                SurveyController::VIEW_ROUTE,
                ['surveyId' => $survey->getId()]
            ).'#actions-' . $trip->getId();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $delete = $form->get('delete');
            if ($delete instanceof SubmitButton && $delete->isClicked()) {
                $deleteHelper->deleteAction($action);

                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', "Action successfully deleted", "The consignment action was deleted.", ['style' => NotificationBanner::STYLE_SUCCESS]));
                return new RedirectResponse($redirectUrl);
            } else {
                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Important', 'Action not deleted', "The request to delete this consignment action was cancelled."));
                return new RedirectResponse($redirectUrl);
            }
        }

        return $this->render('admin/international/action/delete.html.twig', [
            'action' => $action,
            'form' => $form->createView(),
        ]);
    }
}