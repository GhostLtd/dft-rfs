<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\Day;
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
    public const ROUTE_NAME = 'app_domesticsurvey_daystop_init';

    protected $dayNumber;
    private $legNumber;

    /**
     * @Route("/domestic-survey/day-{dayNumber}/stop/{state}", name="app_domesticsurvey_daystop_wizard")
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
     * @Route(
     *     "/domestic-survey/day-{dayNumber}/stop-{legNumber}/start",
     *     name="app_domesticsurvey_daystop_start",
     *     requirements={"legNumber"="\d+|(add)"}
     * )
     * @Route(
     *     "/domestic-survey/day-{dayNumber}/stop-{legNumber}/{state}",
     *     name=self::ROUTE_NAME,
     *     requirements={"legNumber"="\d+|(add)"}
     * )
     * @param WorkflowInterface $domesticSurveyDayStopStateMachine
     * @param Request $request
     * @param $dayNumber
     * @param string $legNumber
     * @param null $state
     * @return Response
     */
    public function init(WorkflowInterface $domesticSurveyDayStopStateMachine, Request $request, $dayNumber, $legNumber = "add", $state = null)
    {
        // set up the forWizard
        $this->dayNumber = intval($dayNumber);
        $this->legNumber = $legNumber;
        $formWizard = $this->getFormWizard();

        // forward to wizard route
        return $this->redirectToRoute('app_domesticsurvey_daystop_wizard', ['dayNumber' => $dayNumber, 'state' => $state]);
    }

    /**
     * @return FormWizardInterface
     */
    protected function getFormWizard(): FormWizardInterface
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        /** @var Day $day */
        $day = $this->entityManager->getRepository(Day::class)->getBySurveyAndDayNumber($user->getDomesticSurvey(), $this->dayNumber);

        /** @var DayStopState $formWizard */
        if ($this->session->has($this->getSessionKey())) {
            $formWizard = $this->session->get($this->getSessionKey());
        } else {
            $formWizard = new DayStopState();
            // check legNumber (add / number)
            if ($this->legNumber === 'add') {
                $day->addStop($dayStop = new DayStop());
            } else {
                $dayStop = $day->getStopByNumber($this->legNumber);
            }
            $formWizard->setSubject($dayStop);
            $this->setFormWizard($formWizard);
        }
//        $dayStop = $this->entityManager->getRepository(DayStop::class)->getBySurveyAndDayNumber($user->getDomesticSurvey(), $this->dayNumber);
//        if (is_null($formWizard->getSubject())) {
//            $formWizard->setSubject($dayStop);
//        }
        $formWizard->getSubject()->setDay($day);

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
        return $this->redirectToRoute('app_domesticsurvey_daystop_wizard', ['dayNumber' => $this->dayNumber, 'state' => $state]);
    }
}
