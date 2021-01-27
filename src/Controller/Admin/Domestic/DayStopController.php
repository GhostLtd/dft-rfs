<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\Survey;
use App\Form\Admin\DomesticSurvey\DayStopDeleteType;
use App\Form\Admin\DomesticSurvey\Edit\DayStopType;
use App\Repository\Domestic\DayRepository;
use App\Utility\Domestic\DeleteHelper;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("")
 */
class DayStopController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_domestic_daystop_';
    public const ADD_ROUTE = self::ROUTE_PREFIX . 'add';
    public const ADD_DAY_AND_STOP_ROUTE = self::ROUTE_PREFIX . 'add_day_and_stop';
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
     * @Route("/survey/{surveyId}/{dayNumber}/add-day-stage", name=self::ADD_DAY_AND_STOP_ROUTE, requirements={"dayNumber": "\d+"})
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function addDayAndStop(Survey $survey, int $dayNumber): Response
    {
        $existingDay = $this->dayRepository->getBySurveyAndDayNumber($survey, $dayNumber);

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

    /**
     * @Route("/days/{dayId}/add-day-stage", name=self::ADD_ROUTE)
     * @Entity("day", expr="repository.find(dayId)")
     */
    public function add(Day $day): Response
    {
        if ($day->getSummary() !== null) {
            throw new BadRequestHttpException();
        }

        $stop = (new DayStop());
        $day->addStop($stop);

        $this->entityManager->persist($stop);

        return $this->handleRequest($stop, "admin/domestic/stop/add.html.twig", ['is_add_form' => true]);
    }

    /**
     * @Route("/csrgt/day-stops/{stopId}", name=self::EDIT_ROUTE)
     * @Entity("stop", expr="repository.find(stopId)")
     */
    public function edit(DayStop $stop)
    {
        return $this->handleRequest($stop, "admin/domestic/stop/edit.html.twig");
    }

    protected function handleRequest(DayStop $stop, string $template, array $formOptions = [])
    {
        $survey = $stop->getDay()->getResponse()->getSurvey();

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
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/csrgt/day-stops/{stopId}/delete", name=self::DELETE_ROUTE)
     * @Entity("stop", expr="repository.find(stopId)")
     */
    public function delete(DayStop $stop, DeleteHelper $deleteHelper)
    {
        $form = $this->createForm(DayStopDeleteType::class);

        $day = $stop->getDay();
        $survey = $day->getResponse()->getSurvey();

        $redirectUrl = $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()])."#{$day->getId()}";

        $translationPrefix = 'domestic.day-stop-delete';
        $request = $this->requestStack->getCurrentRequest();
        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $delete = $form->get('delete');

            $notificationPrefix = "{$translationPrefix}.notification";
            if ($delete instanceof SubmitButton && $delete->isClicked()) {
                $deleteHelper->deleteDayStop($stop);

                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, $deleteHelper->getDeletedNotification($notificationPrefix));
                return new RedirectResponse($redirectUrl);
            } else {
                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, $deleteHelper->getCancelledNotification($notificationPrefix));
                return new RedirectResponse($redirectUrl);
            }
        }

        return $this->render('admin/domestic/stop/delete.html.twig', [
            'stop' => $stop,
            'survey' => $survey,
            'form' => $form->createView(),
            'translation_prefix' => $translationPrefix,
        ]);
    }
}