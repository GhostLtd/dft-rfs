<?php

namespace App\Controller\Admin\Domestic;

use App\Controller\AbstractDayStopReorderController;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\Survey;
use App\Security\Voter\AdminSurveyVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DayStopReorderController extends AbstractDayStopReorderController
{
    protected function getRedirectResponse(Day $day): RedirectResponse
    {
        $survey = $day->getResponse()->getSurvey();
        $url = $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]).'#'.$day->getId();
        return new RedirectResponse($url);
    }

    protected function getTemplate(): string
    {
        return 'admin/domestic/stop/re-order.html.twig';
    }

    /**
     * @Route("/csrgt/surveys/{surveyId}/{dayNumber}/reorder-stops", name=DayStopController::REORDER_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     * @IsGranted(AdminSurveyVoter::EDIT, subject="survey")
     */
    public function reorderAction(Survey $survey, int $dayNumber, Request $request): Response
    {
        return parent::reorder($request, $this->getDay($survey, $dayNumber));
    }
}