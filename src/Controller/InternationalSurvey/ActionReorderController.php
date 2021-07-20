<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\AbstractActionReorderController;
use App\Entity\International\Trip;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/international-survey")
 * @Security("is_granted('EDIT', user.getInternationalSurvey())")
 */
class ActionReorderController extends AbstractActionReorderController
{
    use SurveyHelperTrait;

    protected function getRedirectResponse(Trip $trip): RedirectResponse
    {
        return $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $trip->getId()]);
    }

    protected function getTemplate(): string
    {
        return 'international_survey/action/re-order.html.twig';
    }

    /**
     * @Route("/trips/{tripId}/reorder-actions", name=ActionController::REORDER_ROUTE)
     * @Entity("trip", expr="repository.find(tripId)")
     */
    public function reorderAction(Trip $trip, Request $request): Response
    {
        $survey = $trip->getVehicle()->getSurveyResponse()->getSurvey();
        $sessionSurvey = $this->getSurvey();

        if ($survey->getId() !== $sessionSurvey->getId()) {
            throw new NotFoundHttpException();
        }

        return parent::reorder($request, $trip);
    }
}