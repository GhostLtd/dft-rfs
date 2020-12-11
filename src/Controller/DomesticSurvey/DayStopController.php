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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Class DayStopController
 * @package App\Controller\DomesticSurvey
 * @Route("/domestic-survey/day-{dayNumber}", requirements={"dayNumber"="[1-7]"})
 * @Security("is_granted('EDIT', user.getDomesticSurvey())")
 */
class DayStopController extends AbstractSessionStateWorkflowController
{
    public const START_ROUTE = 'app_domesticsurvey_daystop_start';
    public const WIZARD_ROUTE = 'app_domesticsurvey_daystop_wizard';

    protected $dayNumber;
    protected $stopNumber;

    /** @var Day $day */
    protected $day;
    protected $stop;

    /**
     * @Route(
     *     "/stop-{stopNumber}/start",
     *     name=self::START_ROUTE,
     *     requirements={"stageNumber"="\d+|(add)"}
     * )
     * @Route(
     *     "/stop-{stopNumber}/{state}",
     *     name=self::WIZARD_ROUTE,
     *     requirements={"stageNumber"="\d+|(add)"}
     * )
     */
    public function init(WorkflowInterface $domesticSurveyDayStopStateMachine, Request $request, $dayNumber, $stopNumber = "add", $state = null): Response
    {
        $this->stopNumber = $stopNumber;

        /** @var PasscodeUser $user */
        $user = $this->getUser();

        $this->dayNumber = $dayNumber;

        $this->day = $this->entityManager->getRepository(Day::class)->getBySurveyAndDayNumber($user->getDomesticSurvey(), $dayNumber);
        if (!$this->day) throw new NotFoundHttpException();
        $this->stop = $stopNumber === 'add' ? (new DayStop()) : $this->day->getStopByNumber($stopNumber);

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
        } else {
            $this->day->addStop($subject); // Ultimately causes stop->number to be set
        }

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['dayNumber' => $this->dayNumber, 'stopNumber' => $this->stopNumber, 'state' => $state]);
    }
}
