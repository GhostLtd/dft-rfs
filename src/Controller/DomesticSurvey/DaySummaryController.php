<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use App\Repository\Domestic\DayRepository;
use App\Repository\Domestic\DaySummaryRepository;
use App\Utility\ConfirmAction\Domestic\Admin\DeleteDaySummaryConfirmAction;
use App\Workflow\DomesticSurvey\DaySummaryState;
use App\Workflow\FormWizardStateInterface;
use Doctrine\ORM\NonUniqueResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @Route("/domestic-survey/day-{dayNumber}", requirements={"dayNumber"="[1-7]"})
 * @Security("is_granted('EDIT', user.getDomesticSurvey())")
 */
class DaySummaryController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_PREFIX = 'app_domesticsurvey_daysummary_';

    public const DELETE_ROUTE = self::ROUTE_PREFIX.'delete';
    public const START_ROUTE = self::ROUTE_PREFIX.'start';
    public const WIZARD_ROUTE = self::ROUTE_PREFIX.'wizard';

    protected int $dayNumber;

    use SurveyHelperTrait;

    private DaySummary $daySummary;

    /**
     * @Route("/summary/start", name=self::START_ROUTE)
     * @Route("/summary/{state}", name=self::WIZARD_ROUTE)
     */
    public function index(WorkflowInterface $domesticSurveyDaySummaryStateMachine, Request $request, $dayNumber, $state = null): Response
    {
        $this->dayNumber = intval($dayNumber);
        return $this->doWorkflow($domesticSurveyDaySummaryStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardStateInterface
    {
        $daySummary = $this->getDaySummary();

        $day = $daySummary->getDay();

        /** @var DaySummaryState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new DaySummaryState());
        $subject = $formWizard->getSubject();

        if (is_null($subject)) {
            $formWizard->setSubject($daySummary);
            $subject = $daySummary;
        }

        if (!$day) {
            $day = (new Day())
                ->setResponse($this->getSurvey()->getResponse())
                ->setHasMoreThanFiveStops(true)
                ->setNumber($this->dayNumber);

            $this->entityManager->persist($day);

            $day->setSummary($subject);
        }

        $subject->setDay($day);

        if ($subject->getId()) {
            // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
            $formWizard->setSubject($this->entityManager->merge($subject));
        }

        $this->daySummary = $formWizard->getSubject();
        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['dayNumber' => $this->dayNumber, 'state' => $state]);
    }

    protected function getCancelUrl(): ?Response
    {
        if (!$this->daySummary->getId()) {
            // new summary on this day - redirect to dashbaord
            return $this->redirectToRoute(IndexController::SUMMARY_ROUTE);
        }
        return $this->redirectToRoute(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]);
    }

    /**
     * @Route("/delete-summary-day", name=self::DELETE_ROUTE)
     * @Template("domestic_survey/day_summary/delete.html.twig")
     */
    public function delete(string $dayNumber, DeleteDaySummaryConfirmAction $confirmAction, Request $request)
    {
        $this->dayNumber = intval($dayNumber);
        $daySummary = $this->getDaySummary();

        return $confirmAction
            ->setSubject($daySummary)
            ->controller(
                $request,
                fn() => $this->generateUrl(IndexController::SUMMARY_ROUTE),
                fn() => $this->generateUrl(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]),
            );
    }

    protected function getDaySummary(): DaySummary
    {
        /** @var DayRepository $dayRepository */
        $dayRepository = $this->entityManager->getRepository(Day::class);

        /** @var DaySummaryRepository $daySummaryRepository */
        $daySummaryRepository = $this->entityManager->getRepository(DaySummary::class);

        try {
            $survey = $this->getSurvey();
            $day = $dayRepository->getBySurveyAndDayNumber($survey, $this->dayNumber);

            if ($day) {
                $daySummary = $daySummaryRepository->getBySurveyAndDay($survey, $day);
            }

            if (!$day || !$daySummary) {
                $daySummary = (new DaySummary());

                if ($day) {
                    $daySummary->setDay($day);
                }
            }

            return $daySummary;
        } catch (NonUniqueResultException $e) {
            throw new NotFoundHttpException();
        }
    }
}
