<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\PasscodeUser;
use App\Repository\Domestic\DayRepository;
use App\Utility\ConfirmAction\Domestic\Admin\DeleteDayStopConfirmAction;
use App\Workflow\DomesticSurvey\DayStopState;
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
class DayStopController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_PREFIX = 'app_domesticsurvey_daystop_';

    public const DELETE_ROUTE = self::ROUTE_PREFIX.'delete';
    public const START_ROUTE = self::ROUTE_PREFIX.'start';
    public const WIZARD_ROUTE = self::ROUTE_PREFIX.'wizard';

    protected string $dayNumber;
    protected string $stopNumber;

    /**
     * @Route(
     *     "/stop-{stopNumber}/start",
     *     name=self::START_ROUTE,
     *     requirements={"stopNumber"="\d+|(add)"}
     * )
     * @Route(
     *     "/stop-{stopNumber}/{state}",
     *     name=self::WIZARD_ROUTE,
     *     requirements={"stopNumber"="\d+|(add)"}
     * )
     */
    public function init(WorkflowInterface $domesticSurveyDayStopStateMachine, Request $request, $dayNumber, $stopNumber = "add", $state = null): Response
    {
        $this->stopNumber = $stopNumber;
        $this->dayNumber = $dayNumber;

        return $this->doWorkflow($domesticSurveyDayStopStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardStateInterface
    {
        $stop = $this->getDayStop();
        $day = $stop->getDay();

        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new DayStopState());
        $subject = $formWizard->getSubject();

        if (!$subject || !$subject instanceof DayStop || $subject->getId() !== $stop->getId()) {
            $subject = $stop; // No subject, or we've changed subject
        }

        $subject->setDay($day);
        $formWizard->setSubject($subject);

        if ($subject->getId()) {
            // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
            $formWizard->setSubject($this->entityManager->merge($subject));
        } else {
            $day->addStop($subject); // Ultimately causes stop->number to be set
        }

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['dayNumber' => $this->dayNumber, 'stopNumber' => $this->stopNumber, 'state' => $state]);
    }

    protected function getCancelUrl(): ?Response
    {
        return $this->redirectToRoute(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]);
    }

    /**
     * @Route("/delete-day-stop-{stopNumber}", name=self::DELETE_ROUTE)
     * @Template("domestic_survey/day_stop/delete.html.twig")
     */
    public function delete(string $dayNumber, string $stopNumber, DeleteDayStopConfirmAction $confirmAction, Request $request)
    {
        $this->dayNumber = intval($dayNumber);
        $this->stopNumber = intval($stopNumber);

        $dayStop = $this->getDayStop();
        $day = $dayStop->getDay();

        return $confirmAction
            ->setSubject($dayStop)
            ->controller(
                $request,
                function() use ($day) {
                    return ($day->getStops()->count() > 1) ?
                        $this->generateUrl(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]) :
                        $this->generateUrl('app_domesticsurvey_index');
                },
                fn() => $this->generateUrl(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]),
            );
    }

    protected function getDayStop(): DayStop
    {
        /** @var DayRepository $dayRepository */
        $dayRepository = $this->entityManager->getRepository(Day::class);

        $user = $this->getUser();

        if ($user instanceof PasscodeUser) {
            try {
                $day = $dayRepository->getBySurveyAndDayNumber($user->getDomesticSurvey(), $this->dayNumber);

                if ($day) {
                    $stop = $this->stopNumber === 'add' ? (new DayStop())->setDay($day) : $day->getStopByNumber($this->stopNumber);

                    if ($stop) {
                        return $stop;
                    }
                }

            } catch (NonUniqueResultException $e) {}
        }

        throw new NotFoundHttpException();
    }
}
