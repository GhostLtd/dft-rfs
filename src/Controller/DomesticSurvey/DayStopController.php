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
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getDomesticSurvey())"))]
#[Route(path: '/domestic-survey/day-{dayNumber}', name: 'app_domesticsurvey_daystop_', requirements: ['dayNumber' => '[1-7]'])]
class DayStopController extends AbstractSessionStateWorkflowController
{
    protected string $dayNumber;
    protected string $stopNumber;

    protected ?DayStop $dayStop = null;

    use SurveyHelperTrait;

    #[Route(path: '/stop-{stopNumber}/start', name: 'start', requirements: ['stopNumber' => '\d+|(add)'])]
    #[Route(path: '/stop-{stopNumber}/{state}', name: 'wizard', requirements: ['stopNumber' => '\d+|(add)'])]
    public function init(WorkflowInterface $domesticSurveyDayStopStateMachine, Request $request, $dayNumber, string $stopNumber = "add", $state = null): Response
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

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        $databaseDayStop = $this->getDayStop();

        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new DayStopState());
        $sessionDayStop = $formWizard->getSubject();

        // We have a DayStop in the session, and haven't changed ID (i.e. no user shenanigans)
        if ($sessionDayStop && $sessionDayStop->getId() === $databaseDayStop->getId()) {
            $databaseDayStop->merge($sessionDayStop);
        }

        $formWizard->setSubject($databaseDayStop);
        return $formWizard;
    }

    #[\Override]
    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute('app_domesticsurvey_daystop_wizard', ['dayNumber' => $this->dayNumber, 'stopNumber' => $this->stopNumber, 'state' => $state]);
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        if ($this->stopNumber === 'add' && count($this->dayStop->getDay()->getStops()) <= 1) {
            // first stop on this day - redirect to dashboard
            return $this->redirectToRoute(IndexController::SUMMARY_ROUTE);
        }

        return $this->redirectToRoute(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]);
    }

    #[Route(path: '/delete-day-stop-{stopNumber}', name: 'delete')]
    #[Template('domestic_survey/day_stop/delete.html.twig')]
    public function delete(string $dayNumber, string $stopNumber, DeleteDayStopConfirmAction $confirmAction, Request $request): \Symfony\Component\HttpFoundation\RedirectResponse|array
    {
        $this->dayNumber = $dayNumber;
        $this->stopNumber = $stopNumber;

        $dayStop = $this->getDayStop(false);
        $numStops = $dayStop->getDay()->getStops()->count();

        return $confirmAction
            ->setSubject($dayStop)
            ->controller(
                $request,
                fn() => ($numStops > 1) ?
                    $this->generateUrl(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]) :
                    $this->generateUrl(IndexController::SUMMARY_ROUTE),
                fn() => $this->generateUrl(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]),
            );
    }

    protected function getDayStop(bool $createDayIfNotFound=true): DayStop
    {
        /** @var DayRepository $dayRepository */
        $dayRepository = $this->entityManager->getRepository(Day::class);

        try {
            $day = $dayRepository->getBySurveyAndDayNumber($this->getSurvey(), $this->dayNumber);

            if ($day) {
                if ($this->stopNumber === 'add') {
                    $this->dayStop = new DayStop();
                    $day->addStop($this->dayStop);
                } else {
                    $this->dayStop = $day->getStopByNumber($this->stopNumber);
                }

                if ($this->dayStop) {
                    return $this->dayStop;
                }
            } else {
                if ($createDayIfNotFound) {
                    $day = (new Day())
                        ->setResponse($this->getSurvey()->getResponse())
                        ->setHasMoreThanFiveStops(false)
                        ->setNumber($this->dayNumber);

                    $this->entityManager->persist($day);
                    $this->dayStop = new DayStop();
                    $day->addStop($this->dayStop);

                    return $this->dayStop;
                }
            }
        } catch (NonUniqueResultException) {}

        throw new NotFoundHttpException();
    }
}
