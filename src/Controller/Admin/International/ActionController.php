<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Form\Admin\InternationalSurvey\ActionLoadType;
use App\Form\Admin\InternationalSurvey\ActionUnloadType;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\International\DeleteActionConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/irhs')]
class ActionController extends AbstractController
{
    private const string ROUTE_PREFIX = "admin_international_action_";

    public const ADD_LOADING_ROUTE = self::ROUTE_PREFIX . "add_loading";
    public const ADD_UNLOADING_ROUTE = self::ROUTE_PREFIX . "add_unloading";
    public const DELETE_ROUTE = self::ROUTE_PREFIX . "delete";
    public const EDIT_ROUTE = self::ROUTE_PREFIX . "edit";
    public const REORDER_ROUTE = self::ROUTE_PREFIX . "reorder";

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack           $requestStack
    ) {}

    #[Route(path: '/trip/{tripId}/add-loading-action', name: self::ADD_LOADING_ROUTE)]
    public function addLoadingAction(
        #[MapEntity(expr: "repository.find(tripId)")]
        Trip $trip
    ): Response
    {
        $action = (new Action())->setLoading(true);
        $trip->addAction($action);
        $this->entityManager->persist($action);

        return $this->handleRequest($action, 'admin/international/action/add-loading.html.twig', ['placeholders' => true]);
    }

    #[Route(path: '/trip/{tripId}/add-unloading-action', name: self::ADD_UNLOADING_ROUTE)]
    public function addUnloadingAction(
        #[MapEntity(expr: "repository.find(tripId)")]
        Trip $trip
    ): Response
    {
        $action = (new Action())->setLoading(false);
        $trip->addAction($action);
        $this->entityManager->persist($action);

        return $this->handleRequest($action, 'admin/international/action/add-unloading.html.twig');
    }

    #[Route(path: '/action/{actionId}/edit', name: self::EDIT_ROUTE)]
    public function edit(
        #[MapEntity(expr: "repository.find(actionId)")]
        Action $action
    ): Response
    {
        $template = $action->getLoading() ?
            'admin/international/action/edit-loading.html.twig' :
            'admin/international/action/edit-unloading.html.twig';

        return $this->handleRequest($action, $template);
    }

    protected function handleRequest(
        #[MapEntity(expr: "repository.find(actionId)")]
        Action $action,
        string $template,
        array  $formOptions = []
    ): Response
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
            'form' => $form,
        ]);
    }

    #[Route(path: '/action/{actionId}/delete', name: self::DELETE_ROUTE)]
    #[Template('admin/international/action/delete.html.twig')]
    public function delete(
        #[MapEntity(expr: "repository.findOneByIdWithRelatedActions(actionId)")]
        Action                    $action,
        Request                   $request,
        DeleteActionConfirmAction $confirmAction
    ): RedirectResponse|array
    {
        $trip = $action->getTrip();
        $survey = $trip->getVehicle()->getSurveyResponse()->getSurvey();
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $survey);

        return $confirmAction
            ->setSubject($action)
            ->setExtraViewData([
                'action' => $action,
            ])
            ->controller(
                $request,
                fn() => $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]) . '#actions-' . $trip->getId()
            );
    }
}
