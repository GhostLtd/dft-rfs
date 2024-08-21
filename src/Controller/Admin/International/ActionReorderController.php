<?php

namespace App\Controller\Admin\International;

use App\Controller\AbstractActionReorderController;
use App\Entity\International\Trip;
use App\Security\Voter\AdminSurveyVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/irhs')]
class ActionReorderController extends AbstractActionReorderController
{
    #[\Override]
    protected function getRedirectResponse(Trip $trip): RedirectResponse
    {
        $survey = $trip->getVehicle()->getSurveyResponse()->getSurvey();
        $url = $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]).'#actions-'.$trip->getId();
        return new RedirectResponse($url);
    }

    #[\Override]
    protected function getTemplate(): string
    {
        return 'admin/international/action/re-order.html.twig';
    }

    #[Route(path: '/trips/{tripId}/reorder-actions', name: ActionController::REORDER_ROUTE)]
    public function reorderActions(
        #[MapEntity(expr: "repository.find(tripId)")]
        Trip $trip,
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $trip->getVehicle()->getSurveyResponse()->getSurvey());
        return parent::reorder($request, $trip);
    }
}