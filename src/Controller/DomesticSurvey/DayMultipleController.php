<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\Survey;
use App\Workflow\DomesticSurvey\DayMultipleState;
use App\Workflow\DomesticSurvey\VehicleAndBusinessDetailsState;
use App\Workflow\FormWizardInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class DayMultipleController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_domesticsurvey_daymultiple_index';

    protected $day;
    protected $stage;

    /**
     * @Route("/domestic-survey/day-{day}/stqge/{stage}/{state}", name=self::ROUTE_NAME)
     * @Route("/domestic-survey/day-{day}/start", name="app_domesticsurvey_daymultiple_start")
     * @param WorkflowInterface $domesticSurveyDayMultipleStateMachine
     * @param Request $request
     * @param $day
     * @param string $stage
     * @param null $state
     * @return Response
     * @throws Exception
     */
    public function index(WorkflowInterface $domesticSurveyDayMultipleStateMachine, Request $request, $day, $stage = 'add', $state = null): Response
    {
        $this->day = $day;
        $this->stage = $stage;
        return $this->doWorkflow($domesticSurveyDayMultipleStateMachine, $request, $state);
    }

    /**
     * @return FormWizardInterface
     */
    protected function getFormWizard(): FormWizardInterface
    {
        /** @var DayMultipleState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new DayMultipleState());
        if (is_null($formWizard->getSubject())) {
            if ($this->stage === 'add') {
                // create a new one
                $formWizard->setSubject(new DayStop());
                $this->entityManager->persist($formWizard->getSubject());
                // add the day
            } else {
//                $survey = $this->entityManager->getRepository(Survey::class)->findLatestSurveyForTesting();
                // load the stop and merge it if we already have one for this day/stage
                // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
            }
        }
        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::ROUTE_NAME, ['day' => $this->day, 'stage' => $this->stage, 'state' => $state]);
    }
}
