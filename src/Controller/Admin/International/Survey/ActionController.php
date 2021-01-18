<?php

namespace App\Controller\Admin\International\Survey;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Form\Admin\InternationalSurvey\ActionLoadType;
use App\Form\Admin\InternationalSurvey\ActionUnloadType;
use Doctrine\ORM\EntityManagerInterface;
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
    public const EDIT_ROUTE = self::ROUTE_PREFIX . "edit";
    public const REORDER_ROUTE = self::ROUTE_PREFIX . "reorder";

    protected $entityManager;
    protected $requestStack;

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
        $formClass = $action->getLoading() ? ActionLoadType::class : ActionUnloadType::class;
        $form = $this->createForm($formClass, $action, $formOptions);
        $request = $this->requestStack->getCurrentRequest();

        $unmodifiedAction = clone $action;

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $isValid = $form->isValid();
            if ($isValid) {
                $this->entityManager->flush();
            }

            $cancel = $form->get('cancel');
            if ($isValid || ($cancel instanceof SubmitButton && $cancel->isClicked())) {
                return new RedirectResponse(
                    $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $action->getTrip()->getVehicle()->getSurveyResponse()->getSurvey()->getId()]) .
                    "#actions-{$action->getTrip()->getId()}");
            }
        }

        return $this->render($template, [
            'action' => $unmodifiedAction,
            'form' => $form->createView(),
        ]);
    }
}