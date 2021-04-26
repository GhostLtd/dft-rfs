<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\AbstractDayStopReorderController;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DayStopReorderController extends AbstractDayStopReorderController
{
    protected function getRedirectResponse(Day $day): RedirectResponse
    {
        return $this->redirectToRoute(DayController::VIEW_ROUTE, ['dayNumber' => $day->getNumber()]);
    }

    protected function getTemplate(): string
    {
        return 'domestic_survey/day_stop/re-order.html.twig';
    }

    /**
     * @Route("/domestic-survey/day-{dayNumber}/reorder-stops",
     *   requirements={"dayNumber"="[1-7]"},
     *   name=DayStopController::REORDER_ROUTE)
     * @Security("is_granted('EDIT', user.getDomesticSurvey())")
     */
    public function reorderStops(string $dayNumber, Request $request): Response
    {
        return parent::reorder($request, $this->getDay($this->getSurvey(), $dayNumber));
    }

    protected function getSurvey(): Survey
    {
        $user = $this->getUser();
        $survey = $user instanceof PasscodeUser ? $user->getDomesticSurvey() : null;

        if (!$survey) {
            throw new NotFoundHttpException();
        }

        return $survey;
    }
}