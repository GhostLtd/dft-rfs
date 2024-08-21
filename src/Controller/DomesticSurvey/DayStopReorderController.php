<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\AbstractDayStopReorderController;
use App\Entity\Domestic\Day;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;

class DayStopReorderController extends AbstractDayStopReorderController
{
    use SurveyHelperTrait;

    #[\Override]
    protected function getRedirectResponse(Day $day): RedirectResponse
    {
        return $this->redirectToRoute(DayController::VIEW_ROUTE, ['dayNumber' => $day->getNumber()]);
    }

    #[\Override]
    protected function getTemplate(): string
    {
        return 'domestic_survey/day_stop/re-order.html.twig';
    }

    #[IsGranted(new Expression("is_granted('EDIT', user.getDomesticSurvey())"))]
    #[Route(path: '/domestic-survey/day-{dayNumber}/reorder-stops', name: 'app_domesticsurvey_daystop_reorder', requirements: ['dayNumber' => '[1-7]'])]
    public function reorderStops(string $dayNumber, Request $request): Response
    {
        return parent::reorder($request, $this->getDay($this->getSurvey(), $dayNumber));
    }
}