<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use App\Workflow\DomesticSurvey\DayStopState;
use App\Workflow\DomesticSurvey\DaySummaryState;
use App\Workflow\DomesticSurvey\VehicleAndBusinessDetailsState;
use App\Workflow\FormWizardInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class DayStopController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_domesticsurvey_daystop_index';

    protected $dayNumber;

    /**
     * @Route("/domestic-survey/day-{dayNumber}/stop/start", name="app_domesticsurvey_daystop_start")
     * @Route("/domestic-survey/day-{dayNumber}/stop/{state}", name=self::ROUTE_NAME)
     * @param WorkflowInterface $domesticSurveyDayStopStateMachine
     * @param Request $request
     * @param $dayNumber
     * @param null | string $state
     * @return Response
     * @throws Exception
     */
    public function index(WorkflowInterface $domesticSurveyDayStopStateMachine, Request $request, $dayNumber, $state = null): Response
    {
        $this->dayNumber = intval($dayNumber);
        return $this->doWorkflow($domesticSurveyDayStopStateMachine, $request, $state);
    }

    /**
     * @return FormWizardInterface
     */
    protected function getFormWizard(): FormWizardInterface
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();

        /** @var DayStopState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new DayStopState());
        $dayStop = $this->entityManager->getRepository(DayStop::class)->getBySurveyAndDayNumber($user->getDomesticSurvey(), $this->dayNumber);
        if (is_null($formWizard->getSubject())) {
            $formWizard->setSubject($dayStop);
        }
        $formWizard->getSubject()->setDay($dayStop->getDay());
        if ($formWizard->getSubject()->getId()) {
            // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
            $formWizard->setSubject($this->entityManager->merge($formWizard->getSubject()));
        } else {
            $this->entityManager->persist($formWizard->getSubject());
        }
        $formWizard->getSubject();

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::ROUTE_NAME, ['dayNumber' => $this->dayNumber, 'state' => $state]);
    }
}
