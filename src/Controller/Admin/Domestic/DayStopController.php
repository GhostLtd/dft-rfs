<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\Survey;
use App\Form\Admin\DomesticSurvey\Edit\DayStopType;
use App\Repository\Domestic\DayRepository;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Domestic\Admin\DeleteDayStopConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/csrgt')]
class DayStopController extends AbstractController
{
    private const string ROUTE_PREFIX = 'admin_domestic_daystop_';
    public const ADD_ROUTE = self::ROUTE_PREFIX . 'add';
    public const ADD_DAY_AND_STOP_ROUTE = self::ROUTE_PREFIX . 'add_day_and_stop';
    public const DELETE_ROUTE = self::ROUTE_PREFIX . 'delete';
    public const EDIT_ROUTE = self::ROUTE_PREFIX . 'edit';
    public const REORDER_ROUTE = self::ROUTE_PREFIX . 'reorder';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack           $requestStack,
        protected DayRepository          $dayRepository
    ) {}

    #[Route(path: '/surveys/{surveyId}/{dayNumber}/add-day-stage', name: self::ADD_DAY_AND_STOP_ROUTE, requirements: ['dayNumber' => '\d+'])]
    #[IsGranted(AdminSurveyVoter::EDIT, subject: 'survey')]
    public function addDayAndStop(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey,
        int $dayNumber
    ): Response
    {
        $existingDay = $this->dayRepository->getBySurveyAndDayNumber($survey, $dayNumber);

        if ($existingDay && $existingDay->getSummary() === null && $existingDay->getStops()->isEmpty()) {
            $this->entityManager->remove($existingDay);
            $existingDay = null;
        }

        if ($existingDay || $dayNumber < 1 || $dayNumber > $survey->getSurveyPeriodInDays()) {
            throw new BadRequestHttpException();
        }

        $stop = (new DayStop());
        $day = (new Day())
            ->setNumber($dayNumber)
            ->setResponse($survey->getResponse())
            ->setHasMoreThanFiveStops(false)
            ->addStop($stop);

        $this->entityManager->persist($day);
        $this->entityManager->persist($stop);

        return $this->handleRequest($stop, "admin/domestic/stop/add.html.twig", ['is_add_form' => true]);
    }

    #[Route(path: '/days/{dayId}/add-day-stage', name: self::ADD_ROUTE)]
    public function add(
        #[MapEntity(expr: "repository.find(dayId)")]
        Day $day
    ): Response
    {
        if ($day->getSummary() !== null) {
            throw new BadRequestHttpException();
        }

        $stop = (new DayStop());
        $day->addStop($stop);

        $this->entityManager->persist($stop);

        return $this->handleRequest($stop, "admin/domestic/stop/add.html.twig", ['is_add_form' => true]);
    }

    #[Route(path: '/day-stages/{stopId}', name: self::EDIT_ROUTE)]
    public function edit(
        #[MapEntity(expr: "repository.find(stopId)")]
        DayStop $stop
    ): Response
    {
        return $this->handleRequest($stop, "admin/domestic/stop/edit.html.twig");
    }

    protected function handleRequest(DayStop $stop, string $template, array $formOptions = []): Response
    {
        $survey = $stop->getDay()->getResponse()->getSurvey();
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $survey);

        $form = $this->createForm(DayStopType::class, $stop, $formOptions);
        $request = $this->requestStack->getCurrentRequest();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $redirectResponse = new RedirectResponse($this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]) . '#' . $stop->getId());

            $cancel = $form->get('cancel');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return $redirectResponse;
            }

            if ($form->isValid()) {
                $this->entityManager->flush();
                return $redirectResponse;
            }
        }

        return $this->render($template, [
            'survey' => $survey,
            'form' => $form,
        ]);
    }

    #[Route(path: '/day-stages/{stopId}/delete', name: self::DELETE_ROUTE)]
    #[Template('admin/domestic/stop/delete.html.twig')]
    public function delete(
        #[MapEntity(expr: "repository.findOneForDelete(stopId)")]
        DayStop $stop,
        DeleteDayStopConfirmAction $deleteDayStopConfirmAction,
        Request $request
    ): RedirectResponse|array
    {
        $day = $stop->getDay();
        $survey = $day->getResponse()->getSurvey();
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $survey);

        return $deleteDayStopConfirmAction
            ->setSubject($stop)
            ->controller(
                $request,
                fn() => $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]) . "#{$day->getId()}"
            );
    }
}
