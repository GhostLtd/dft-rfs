<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Form\DomesticSurvey\CreateDay\NumberOfStopsType;
use App\Workflow\DomesticSurveyInitialDetailsState;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class IndexController
 * @package App\Controller\DomesticSurvey
 * @Route("/domestic-survey")
 */
class DayController extends AbstractController
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $dayNumber
     * @return Response
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @Route("/day-{dayNumber}")
     */
    public function view(EntityManagerInterface $entityManager, Request $request, $dayNumber)
    {
        // load the survey
        $survey = $entityManager->getRepository(Survey::class)->findLatestSurveyForTesting();

        // fetch day for survey/dayNumber
        $day = $entityManager->getRepository(Day::class)->findOneBy(['number' => $dayNumber, 'response' => $survey->getResponse()]);

        if ($day) {
            // if there is already a day, show the day summary screen
            return $this->render('domestic_survey/day-summary.html.twig', [
                'day' => $day,
            ]);
        } else {
            // no day, redirect to add
            return $this->redirectToRoute('app_domesticsurvey_day_add', ['dayNumber' => $dayNumber]);
        }
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $dayNumber
     * @return Response
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @Route("/day-{dayNumber}/add")
     */
    public function add(EntityManagerInterface $entityManager, Request $request, $dayNumber)
    {
        // load the survey
        $survey = $entityManager->getRepository(Survey::class)->findLatestSurveyForTesting();

        // user might have navigated back in order to change answer... lets let them change it.

        // fetch day for survey/dayNumber
        $day = $entityManager->getRepository(Day::class)->findOneBy(['number' => $dayNumber, 'response' => $survey->getResponse()]);

        if (!$day) {
            $day = (new Day())
                ->setNumber($dayNumber)
                ->setResponse($survey->getResponse());
        }

        /** @var Form $form */
        $form = $this->createForm(NumberOfStopsType::class, $day);

        $form->handleRequest($request);
        $clickedButton = $form->getClickedButton();

        if ($clickedButton && $clickedButton->getName() === 'continue') {
            $entityManager->persist($day);
            $entityManager->flush();

            // redirect back here... the summary screen
            return $this->redirectToRoute('app_domesticsurvey_day_view', ['dayNumber' => $dayNumber]);
        }

        // show the form/template
        return $this->render('domestic_survey/create-day.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
