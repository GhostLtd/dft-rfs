<?php

namespace App\Controller\Admin\International\Survey;

use App\Controller\AbstractActionReorderController;
use App\Entity\International\Trip;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs")
 */
class ActionReorderController extends AbstractActionReorderController
{
    protected function getRedirectResponse(Trip $trip): RedirectResponse
    {
        $survey = $trip->getVehicle()->getSurveyResponse()->getSurvey();
        $url = $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]).'#actions-'.$trip->getId();
        return new RedirectResponse($url);
    }

    protected function getTemplate(): string
    {
        return 'admin/international/action/re-order.html.twig';
    }

    /**
     * @Route("/trips/{tripId}/reorder-actions", name=ActionController::REORDER_ROUTE)
     * @Entity("trip", expr="repository.find(tripId)")
     */
    public function reorderAction(Trip $trip, Request $request): Response
    {
        return parent::reorder($request, $trip);
    }
}