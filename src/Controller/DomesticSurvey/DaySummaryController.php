<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoType;
use App\Entity\Distance;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use App\Exception\DayTypeMismatchException;
use App\Repository\Domestic\DayRepository;
use App\Repository\Domestic\DaySummaryRepository;
use App\Utility\ConfirmAction\Domestic\Admin\DeleteDaySummaryConfirmAction;
use App\Workflow\DomesticSurvey\DayStopState;
use App\Workflow\DomesticSurvey\DaySummaryState;
use App\Workflow\FormWizardStateInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getDomesticSurvey())"))]
#[Route(path: '/domestic-survey/day-{dayNumber}', name: 'app_domesticsurvey_daysummary_', requirements: ['dayNumber' => '[1-7]'])]
class DaySummaryController extends AbstractSessionStateWorkflowController
{
    protected int $dayNumber;

    use SurveyHelperTrait;

    private DaySummary $daySummary;

    public function getSessionKey(): string
    {
        $routeParams = $this->requestStack->getCurrentRequest()->attributes->get('_route_params', []);
        $dayNumber = 'day-' . $routeParams['dayNumber'];
        $class = static::class;
        return "wizard.{$class}.summary.{$dayNumber}";
    }

    #[Route(path: '/summary/start', name: 'start')]
    #[Route(path: '/summary/{state}', name: 'wizard')]
    public function index(WorkflowInterface $domesticSurveyDaySummaryStateMachine, Request $request, $dayNumber, $state = null): Response
    {
        $this->dayNumber = intval($dayNumber);

        $additionalViewData = [];
        if ($state === DayStopState::STATE_INTRO) {
            $additionalViewData['exampleSummaryDay'] = $this->getExampleDay();
        }

        try {
            return $this->doWorkflow($domesticSurveyDaySummaryStateMachine, $request, $state, $additionalViewData);
        }
        catch(DayTypeMismatchException) {
            return $this->redirectToRoute('app_domesticsurvey_day_view', ['dayNumber' => $dayNumber]);
        }
    }

    protected function getExampleDay(): Day
    {
        return (new Day())
            ->setSummary((new DaySummary())
                ->setOriginLocation('HD1 3LE')
                ->setDestinationLocation('HD1 3LE')
                ->setGoodsLoaded(true)
                ->setGoodsUnloaded(true)
                ->setFurthestStop('CV3 2NT')
                ->setDistanceTravelledLoaded((new Distance())->setValue(192)->setUnit(Distance::UNIT_MILES))
                ->setDistanceTravelledUnloaded((new Distance())->setValue(35)->setUnit(Distance::UNIT_MILES))
                ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_GROUPAGE)
                ->setCargoTypeCode(CargoType::CODE_PL_PALLETISED_GOODS)
                ->setWeightOfGoodsLoaded(50000)
                ->setWeightOfGoodsLoaded(50000)
                ->setNumberOfStopsLoading(1)
                ->setNumberOfStopsUnloading(2)
                ->setNumberOfStopsLoadingAndUnloading(2)
            )
            ->setNumber(1)
            ->setHasMoreThanFiveStops(true)
            ;
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        $databaseDaySummary = $this->getDaySummary();

        /** @var DaySummaryState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new DaySummaryState());
        $sessionDaySummary = $formWizard->getSubject();

        if ($sessionDaySummary && $sessionDaySummary->getId() === $databaseDaySummary->getId()) {
            $databaseDaySummary->merge($sessionDaySummary);
        }

        $formWizard->setSubject($databaseDaySummary);
        return $formWizard;
    }

    #[\Override]
    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute('app_domesticsurvey_daysummary_wizard', ['dayNumber' => $this->dayNumber, 'state' => $state]);
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        if (!$this->daySummary->getId()) {
            // new summary on this day - redirect to dashboard
            return $this->redirectToRoute(IndexController::SUMMARY_ROUTE);
        }
        return $this->redirectToRoute(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]);
    }

    #[Route(path: '/delete-summary-day', name: 'delete')]
    #[Template('domestic_survey/day_summary/delete.html.twig')]
    public function delete(string $dayNumber, DeleteDaySummaryConfirmAction $confirmAction, Request $request): RedirectResponse|array
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
            $daySummary = null;

            if ($day) {
                if (!$day->getStops()->isEmpty()) {
                    // This is already a detailed day (one with stops), so you can't use the summary wizard
                    throw new DayTypeMismatchException();
                }

                $daySummary = $daySummaryRepository->getBySurveyAndDay($survey, $day);
            }

            if (!$daySummary) {
                $daySummary = (new DaySummary());
            }

            if (!$day) {
                $day = (new Day())
                    ->setResponse($this->getSurvey()->getResponse())
                    ->setHasMoreThanFiveStops(true)
                    ->setNumber($this->dayNumber);

                $this->entityManager->persist($day);

                $day->setSummary($daySummary);
            }

            $this->daySummary = $daySummary;
            return $daySummary;
        } catch (NonUniqueResultException) {
            throw new NotFoundHttpException();
        }
    }
}
