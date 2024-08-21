<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\AbstractActionReorderController;
use App\Entity\International\Trip;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
#[Route(path: '/international-survey')]
class ActionReorderController extends AbstractActionReorderController
{
    use SurveyHelperTrait;

    #[\Override]
    protected function getRedirectResponse(Trip $trip): RedirectResponse
    {
        return $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $trip->getId()]);
    }

    #[\Override]
    protected function getTemplate(): string
    {
        return 'international_survey/action/re-order.html.twig';
    }

    #[Route(path: '/trips/{tripId}/reorder-actions', name: 'app_internationalsurvey_action_reorder')]
    public function reorderActions(
        #[MapEntity(expr: "repository.find(tripId)")]
        Trip $trip,
        Request $request
    ): Response
    {
        $survey = $trip->getVehicle()->getSurveyResponse()->getSurvey();
        $sessionSurvey = $this->getSurvey();

        if ($survey->getId() !== $sessionSurvey->getId()) {
            throw new NotFoundHttpException();
        }

        return parent::reorder($request, $trip);
    }
}
