<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\PasscodeUser;
use App\Workflow\DomesticSurvey\DayStopState;
use App\Workflow\FormWizardInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class DayStopController extends AbstractSessionStateWorkflowController
{
    public const START_ROUTE = 'app_domesticsurvey_daystop_start';
    public const WIZARD_ROUTE = 'app_domesticsurvey_daystop_wizard';

    protected $dayNumber;
    protected $stopNumber;

    protected $day;
    protected $stop;

    /**
     * @Route(
     *     "/domestic-survey/day-{dayNumber}/stop-{stopNumber}/start",
     *     name=self::START_ROUTE,
     *     requirements={"stageNumber"="\d+|(add)"}
     * )
     * @Route(
     *     "/domestic-survey/day-{dayNumber}/stop-{stopNumber}/{state}",
     *     name=self::WIZARD_ROUTE,
     *     requirements={"stageNumber"="\d+|(add)"}
     * )
     * @Security("is_granted('EDIT', user.getDomesticSurvey())")
     */
    public function init(WorkflowInterface $domesticSurveyDayStopStateMachine, Request $request, $dayNumber, $stopNumber = "add", $state = null): Response
    {
        $this->stopNumber = $stopNumber;

        /** @var PasscodeUser $user */
        $user = $this->getUser();

        /** @var Day $day */
        $this->day = $this->entityManager->getRepository(Day::class)->getBySurveyAndDayNumber($user->getDomesticSurvey(), $dayNumber);
        $this->dayNumber = $dayNumber;

        if ($stopNumber === 'add') {
            $this->stop = new DayStop();
            $this->day->addStop($this->stop);
        } else {
            $this->stop = $this->day->getStopByNumber($stopNumber);
        }

        return $this->doWorkflow($domesticSurveyDayStopStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var FormWizardInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new DayStopState());
        $subject = $formWizard->getSubject();

        if (!$subject || !$subject instanceof DayStop || $subject->getId() !== $this->stop->getId()) {
            $subject = $this->stop; // No subject, or we've changed subject
        }

        $subject->setDay($this->day);
        $formWizard->setSubject($subject);

        if ($subject->getId()) {
            // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
            $formWizard->setSubject($this->entityManager->merge($subject));
        }

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['dayNumber' => $this->dayNumber, 'stopNumber' => $this->stopNumber, 'state' => $state]);
    }
}
