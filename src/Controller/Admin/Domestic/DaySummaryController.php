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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt")
 */
class DaySummaryController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_domestic_daysummary_';
    public const ADD_DAY_AND_SUMMARY_ROUTE = self::ROUTE_PREFIX . 'add_day_and_summary';
    public const DELETE_ROUTE = self::ROUTE_PREFIX . 'delete';
    public const EDIT_ROUTE = self::ROUTE_PREFIX . 'edit';

    protected DayRepository $dayRepository;
    protected EntityManagerInterface $entityManager;
    protected RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack, DayRepository $dayRepository)
    {
        $this->dayRepository = $dayRepository;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/surveys/{surveyId}/{dayNumber}/add-day-summary", name=self::ADD_DAY_AND_SUMMARY_ROUTE, requirements={"dayNumber": "\d+"})
     * @Entity("survey", expr="repository.find(surveyId)")
     * @IsGranted(AdminSurveyVoter::EDIT, subject="survey")
     */
    public function addDayAndStop(Survey $survey, int $dayNumber): Response
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

    /**
     * @Route("/day-summaries/{summaryId}", name=self::EDIT_ROUTE)
     * @Entity("summary", expr="repository.find(summaryId)")
     */
    public function edit(DaySummary $summary)
    {
        return $this->handleRequest($summary, "admin/domestic/summary/edit.html.twig");
    }

    protected function handleRequest(DaySummary $summary, string $template, array $formOptions = [])
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
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/day-summaries/{summaryId}/delete", name=self::DELETE_ROUTE)
     * @Entity("summary", expr="repository.find(summaryId)")
     * @Template("admin/domestic/summary/delete.html.twig")
     */
    public function delete(DaySummary $summary, DeleteDaySummaryConfirmAction $confirmAction, Request $request)
    {
        $day = $summary->getDay();
        $survey = $day->getResponse()->getSurvey();
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $survey);

        return $confirmAction
            ->setSubject($summary)
            ->controller(
                $request,
                fn() => $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()])."#{$day->getId()}"
            );
    }
}