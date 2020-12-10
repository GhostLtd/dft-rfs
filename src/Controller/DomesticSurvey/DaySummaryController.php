<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\DaySummary;
use App\Entity\PasscodeUser;
use App\Workflow\DomesticSurvey\DaySummaryState;
use App\Workflow\FormWizardInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class DaySummaryController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_domesticsurvey_daysummary_index';

    protected $dayNumber;

    /**
     * @Route("/domestic-survey/day-{dayNumber}/summary/start", name="app_domesticsurvey_daysummary_start")
     * @Route("/domestic-survey/day-{dayNumber}/summary/{state}", name=self::ROUTE_NAME)
     * @Security("is_granted('EDIT', user.getDomesticSurvey())")
     * @param WorkflowInterface $domesticSurveyDaySummaryStateMachine
     * @param Request $request
     * @param $dayNumber
     * @param null | string $state
     * @return Response
     * @throws Exception
     */
    public function index(WorkflowInterface $domesticSurveyDaySummaryStateMachine, Request $request, $dayNumber, $state = null): Response
    {
        $this->dayNumber = intval($dayNumber);
        return $this->doWorkflow($domesticSurveyDaySummaryStateMachine, $request, $state);
    }

    /**
     * @return FormWizardInterface
     */
    protected function getFormWizard(): FormWizardInterface
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();

        /** @var DaySummaryState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new DaySummaryState());
        $daySummary = $this->entityManager->getRepository(DaySummary::class)->getBySurveyAndDayNumber($user->getDomesticSurvey(), $this->dayNumber);
        if (is_null($formWizard->getSubject())) {
            $formWizard->setSubject($daySummary);
        }
        $formWizard->getSubject()->setDay($daySummary->getDay());
        if ($formWizard->getSubject()->getId()) {
            // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
            $formWizard->setSubject($this->entityManager->merge($formWizard->getSubject()));
        }

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::ROUTE_NAME, ['dayNumber' => $this->dayNumber, 'state' => $state]);
    }
}
