<?php

namespace App\Controller\Admin\International\Survey;

use App\Entity\International\Action;
use App\Form\Admin\InternationalSurvey\ActionLoadType;
use App\Form\Admin\InternationalSurvey\ActionUnloadType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs")
 */
class ActionController extends AbstractController
{
    public const EDIT_ROUTE = "admin_international_action_edit";

    /**
     * @Route("/action/{actionId}/edit", name=self::EDIT_ROUTE)
     * @Entity("action", expr="repository.find(actionId)")
     */
    public function edit(Action $action, Request $request, EntityManagerInterface $entityManager): Response
    {
        $formClass = $action->getLoading() ? ActionLoadType::class : ActionUnloadType::class;
        $form = $this->createForm($formClass, $action);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $isValid = $form->isValid();
            if ($isValid) {
                $entityManager->flush();
            }

            $cancel = $form->get('cancel');
            if ($isValid || ($cancel instanceof SubmitButton && $cancel->isClicked())) {
                return new RedirectResponse(
                    $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $action->getTrip()->getVehicle()->getSurveyResponse()->getSurvey()->getId()]) .
                    "#actions-{$action->getTrip()->getId()}");
            }
        }

        $templateName = $action->getLoading() ?
            'admin/international/action/edit-loading.html.twig' :
            'admin/international/action/edit-unloading.html.twig';

        return $this->render($templateName, [
            'action' => $action,
            'form' => $form->createView(),
        ]);
    }
}