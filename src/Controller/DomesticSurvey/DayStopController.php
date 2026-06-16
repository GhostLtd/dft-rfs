<?php

namespace App\Controller\DomesticSurvey;

use App\Entity\AbstractGoodsDescription;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Exception\DayTypeMismatchException;
use App\Repository\Domestic\DayRepository;
use App\Utility\ConfirmAction\Domestic\Admin\DeleteDayStopConfirmAction;
use App\Utility\PostcodeChangeoverHelper;
use App\Workflow\DomesticSurvey\DayStopState;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression("is_granted('EDIT', user.getDomesticSurvey())"))]
#[Route(path: '/domestic-survey/day-{dayNumber}', name: 'app_domesticsurvey_daystop_', requirements: ['dayNumber' => '[1-7]'])]
class DayStopController extends AbstractSessionStateFormWizardController
{
    protected int $dayNumber;
    protected string $stopNumber;

    protected ?DayStop $dayStop = null;

    use SurveyHelperTrait;

    public function __construct(
        FormWizardManager                $formWizardManager,
        protected RequestStack           $requestStack,
        PropertyMerger                   $propertyMerger,
        protected EntityManagerInterface $entityManager,
        protected PostcodeChangeoverHelper $postcodeChangeoverHelper,
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    public function getSessionKey(): string
    {
        $routeParams = $this->requestStack->getCurrentRequest()->attributes->get('_route_params', []);
        $dayNumber = 'day-' . ($routeParams['dayNumber'] ?? $this->dayNumber);
        $class = static::class;
        return "wizard.{$class}.stop.{$dayNumber}";
    }

    #[Route(path: '/stop-{stopNumber}/start', name: 'start', requirements: ['stopNumber' => '\d+|(add)'])]
    #[Route(path: '/stop-{stopNumber}/{state}', name: 'wizard', requirements: ['stopNumber' => '\d+|(add)'])]
    public function init(Request $request, int $dayNumber, string $stopNumber = "add", ?string $state = null): Response
    {
        $this->stopNumber = $stopNumber;
        $this->dayNumber = $dayNumber;

        $additionalViewData = [];
        if ($state === DayStopState::STATE_INTRO) {
            $additionalViewData['exampleDayStops'] = $this->getExampleDayStops();
        }

        try {
            return $this->doWorkflow($request, $state, $additionalViewData);
        }
        catch(DayTypeMismatchException) {
            return $this->redirectToRoute('app_domesticsurvey_day_view', ['dayNumber' => $dayNumber]);
        }
    }

    protected function getExampleDayStops(): array
    {
        // Use postcode instead of place name when postcode-only mode is active
        $binley = $this->postcodeChangeoverHelper->isPostcodeOnly($this->getSurvey()) ? 'CV3 2NU' : 'Binley';

        return [
            (new DayStop())
                ->setNumber(1)
                ->setOriginLocation('HD1 3LE')
                ->setDestinationLocation($binley)
                ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER)
                ->setGoodsDescriptionOther('Building materials')
                ->setGoodsLoaded(true)
                ->setGoodsUnloaded(true),
            (new DayStop())
                ->setNumber(2)
                ->setOriginLocation($binley)
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

    protected function getState(): FormWizardStateInterface
    {
        $databaseDayStop = $this->getDayStop();

        /** @var DayStopState|null $state */
        $state = $this->session->get($this->getSessionKey(), null);

        if (!$state) {
            $state = new DayStopState();
            $state->setSubject($databaseDayStop);
        }

        $sessionDayStop = $state->getSubject();
        if ($sessionDayStop->getId() === $databaseDayStop->getId()) {
            $databaseDayStop->merge($sessionDayStop);
        }

        $state->setSubject($databaseDayStop);
        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_domesticsurvey_daystop_wizard', [
            'dayNumber' => $this->dayNumber,
            'stopNumber' => $this->stopNumber,
            'state' => (string) $place,
        ]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        if ($this->stopNumber === 'add' && count($this->dayStop->getDay()->getStops()) <= 1) {
            // first stop on this day - redirect to dashboard
            return new RedirectResponse($this->generateUrl(IndexController::SUMMARY_ROUTE));
        }

        return new RedirectResponse($this->generateUrl(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]));
    }

    #[Route(path: '/delete-day-stop-{stopNumber}', name: 'delete')]
    #[Template('domestic_survey/day_stop/delete.html.twig')]
    public function delete(int $dayNumber, string $stopNumber, DeleteDayStopConfirmAction $confirmAction, Request $request): RedirectResponse|array
    {
        $this->dayNumber = $dayNumber;
        $this->stopNumber = $stopNumber;

        $dayStop = $this->getDayStop(false);
        $numStops = $dayStop->getDay()->getStops()->count();

        return $confirmAction
            ->setSubject($dayStop)
            ->controller(
                $request,
                ($numStops > 1) ?
                    $this->generateUrl(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]) :
                    $this->generateUrl(IndexController::SUMMARY_ROUTE),
                $this->generateUrl(DayController::VIEW_ROUTE, ['dayNumber' => $this->dayNumber]),
            );
    }

    protected function getDayStop(bool $createDayIfNotFound=true): DayStop
    {
        /** @var DayRepository $dayRepository */
        $dayRepository = $this->entityManager->getRepository(Day::class);

        try {
            $day = $dayRepository->getBySurveyAndDayNumber($this->getSurvey(), $this->dayNumber);

            if ($day) {
                if ($day->getSummary() !== null) {
                    throw new DayTypeMismatchException();
                }

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

