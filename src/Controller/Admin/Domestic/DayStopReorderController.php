<?php

namespace App\Controller\Admin\Domestic;

use App\Controller\AbstractDayStopReorderController;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\Survey;
use App\Security\Voter\AdminSurveyVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DayStopReorderController extends AbstractDayStopReorderController
{
    #[\Override]
    protected function getRedirectResponse(Day $day): RedirectResponse
    {
        $survey = $day->getResponse()->getSurvey();
        $url = $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]).'#'.$day->getId();
        return new RedirectResponse($url);
    }

    #[\Override]
    protected function getTemplate(): string
    {
        return 'admin/domestic/stop/re-order.html.twig';
    }

    #[Route(path: '/csrgt/surveys/{surveyId}/{dayNumber}/reorder-stops', name: DayStopController::REORDER_ROUTE)]
    #[IsGranted(AdminSurveyVoter::EDIT, subject: 'survey')]
    public function reorderStops(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey,
        int $dayNumber,
        Request $request
    ): Response
    {
        return parent::reorder($request, $this->getDay($survey, $dayNumber));
    }
}