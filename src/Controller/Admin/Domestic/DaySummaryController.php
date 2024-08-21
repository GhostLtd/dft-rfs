<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use App\Form\Admin\DomesticSurvey\Edit\DaySummaryType;
use App\Repository\Domestic\DayRepository;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Domestic\Admin\DeleteDaySummaryConfirmAction;
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
class DaySummaryController extends AbstractController
{
    private const string ROUTE_PREFIX = 'admin_domestic_daysummary_';
    public const ADD_DAY_AND_SUMMARY_ROUTE = self::ROUTE_PREFIX . 'add_day_and_summary';
    public const DELETE_ROUTE = self::ROUTE_PREFIX . 'delete';
    public const EDIT_ROUTE = self::ROUTE_PREFIX . 'edit';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack           $requestStack,
        protected DayRepository          $dayRepository
    ) {}

    #[Route(path: '/surveys/{surveyId}/{dayNumber}/add-day-summary', name: self::ADD_DAY_AND_SUMMARY_ROUTE, requirements: ['dayNumber' => '\d+'])]
    #[IsGranted(AdminSurveyVoter::EDIT, subject: 'survey')]
    public function addDayAndStop(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey,
        int    $dayNumber
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

        $summary = new DaySummary();
        $day = (new Day())
            ->setNumber($dayNumber)
            ->setResponse($survey->getResponse())
            ->setHasMoreThanFiveStops(true)
            ->setSummary($summary);

        $this->entityManager->persist($day);
        $this->entityManager->persist($summary);

        return $this->handleRequest($summary, "admin/domestic/summary/add.html.twig", ['is_add_form' => true]);
    }

    #[Route(path: '/day-summaries/{summaryId}', name: self::EDIT_ROUTE)]
    public function edit(
        #[MapEntity(expr: "repository.find(summaryId)")]
        DaySummary $summary
    ): Response
    {
        return $this->handleRequest($summary, "admin/domestic/summary/edit.html.twig");
    }

    protected function handleRequest(DaySummary $summary, string $template, array $formOptions = []): Response
    {
        $survey = $summary->getDay()->getResponse()->getSurvey();
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $survey);

        $form = $this->createForm(DaySummaryType::class, $summary, $formOptions);

        $request = $this->requestStack->getCurrentRequest();
        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $redirectResponse = new RedirectResponse($this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]) . '#' . $summary->getId());

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

    #[Route(path: '/day-summaries/{summaryId}/delete', name: self::DELETE_ROUTE)]
    #[Template('admin/domestic/summary/delete.html.twig')]
    public function delete(
        #[MapEntity(expr: "repository.find(summaryId)")]
        DaySummary                    $summary,
        DeleteDaySummaryConfirmAction $confirmAction,
        Request                       $request
    ): RedirectResponse|array
    {
        $day = $summary->getDay();
        $survey = $day->getResponse()->getSurvey();
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $survey);

        return $confirmAction
            ->setSubject($summary)
            ->controller(
                $request,
                fn() => $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]) . "#{$day->getId()}"
            );
    }
}
