<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\AbstractGoodsDescription;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
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
 * @Route("/domestic-survey/day-{dayNumber}", requirements={"dayNumber"="[1-7]"}, name="app_domesticsurvey_daystop_")
 * @Security("is_granted('EDIT', user.getDomesticSurvey())")
 */
class DayStopController extends AbstractSessionStateWorkflowController
{
    protected string $dayNumber;
    protected string $stopNumber;

    protected ?DayStop $dayStop = null;

    use SurveyHelperTrait;

    /**
     * @Route(
     *     "/stop-{stopNumber}/start",
     *     name="start",
     *     requirements={"stopNumber"="\d+|(add)"}
     * )
     * @Route(
     *     "/stop-{stopNumber}/{state}",
     *     name="wizard",
     *     requirements={"stopNumber"="\d+|(add)"}
     * )
     */
    public function init(WorkflowInterface $domesticSurveyDayStopStateMachine, Request $request, $dayNumber, $stopNumber = "add", $state = null): Response
    {
        $this->stopNumber = $stopNumber;
        $this->dayNumber = $dayNumber;

        $additionalViewData = [];
        if ($state === DayStopState::STATE_INTRO) {
            $additionalViewData['exampleDayStops'] = $this->getExampleDayStops();
        }

        return $this->doWorkflow($domesticSurveyDayStopStateMachine, $request, $state, $additionalViewData);
    }

    protected function getExampleDayStops(): array
    {
        return [
            (new DayStop())
                ->setNumber(1)
                ->setOriginLocation('HD1 3LE')
                ->setDestinationLocation('Binley')
                ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER)
                ->setGoodsDescriptionOther('Building materials')
                ->setGoodsLoaded(true)
                ->setGoodsUnloaded(true),
            (new DayStop())
                ->setNumber(2)
                ->setOriginLocation('Binley')
                ->setDestinationLocation('S20 3FF')
                ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_GROUPAGE)
                ->setGoodsLoaded(true)
                ->setGoodsUnloaded(true),
            (new DayStop())
                ->setNumber(3)
                ->setOriginLocation('S20 3FF')
                ->setDestinationLocation('S20 8GN')
                ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_GROUPAGE)
                ->setGoodsUnloaded(true),
            (new DayStop())
                ->setNumber(4)
                ->setOriginLocation('S20 8GN')
                ->setDestinationLocation('HD1 3LE')
                ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY),
        ];
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

        if (!$day) {
            $day = (new Day())
                ->setResponse($this->getSurvey()->getResponse())
                ->setHasMoreThanFiveStops(false)
                ->setNumber($this->dayNumber);

            $this->entityManager->persist($day);
        }

        $subject->setDay($day);
        $formWizard->setSubject($subject);

        if ($subject->getId()) {
            // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
            $formWizard->setSubject($this->entityManager->merge($subject));
        } else {
            $day->addStop($subject); // Ultimately causes stop->number to be set
        }

        $this->dayStop = $formWizard->getSubject();
        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute('app_domesticsurvey_daystop_wizard', ['dayNumber' => $this->dayNumber, 'stopNumber' => $this->stopNumber, 'state' => $state]);
    }

    protected function getCancelUrl(): ?Response
    {
        if ($this->stopNumber === 'add' && count($this->dayStop->getDay()->getStops()) <= 1) {
            // first stop on this day - redirect to dashbaord
            return $this->redirectToRoute(IndexController::SUMMARY_ROUTE);
        }

        return $this->redirectToRoute(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]);
    }

    /**
     * @Route("/delete-day-stop-{stopNumber}", name="delete")
     * @Template("domestic_survey/day_stop/delete.html.twig")
     */
    public function delete(string $dayNumber, string $stopNumber, DeleteDayStopConfirmAction $confirmAction, Request $request)
    {
        $this->dayNumber = intval($dayNumber);
        $this->stopNumber = intval($stopNumber);

        $dayStop = $this->getDayStop(false);
        $numStops = $dayStop->getDay()->getStops()->count();

        return $confirmAction
            ->setSubject($dayStop)
            ->controller(
                $request,
                function() use ($numStops) {
                    return ($numStops > 1) ?
                        $this->generateUrl(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]) :
                        $this->generateUrl(IndexController::SUMMARY_ROUTE);
                },
                fn() => $this->generateUrl(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]),
            );
    }

    protected function getDayStop(bool $createIfNotFound=true): DayStop
    {
        /** @var DayRepository $dayRepository */
        $dayRepository = $this->entityManager->getRepository(Day::class);

        try {
            $day = $dayRepository->getBySurveyAndDayNumber($this->getSurvey(), $this->dayNumber);

            if ($day) {
                $stop = $this->stopNumber === 'add' ?
                    (new DayStop())->setDay($day) :
                    $day->getStopByNumber($this->stopNumber);

                if ($stop) {
                    return $stop;
                }
            } else {
                if ($createIfNotFound) {
                    return (new DayStop());
                }
            }
        } catch (NonUniqueResultException $e) {}

        throw new NotFoundHttpException();
    }
}
