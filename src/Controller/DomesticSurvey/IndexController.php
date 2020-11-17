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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class IndexController
 * @package App\Controller\DomesticSurvey
 * @Route("/domestic-survey")
 */
class IndexController extends AbstractController
{
    /**
     * @Route(name="domestic_survey_index")
     * @return Response
     */
    public function index(): Response
    {
        $domesticSurveys = $this->getDoctrine()->getRepository(Survey::class)->findAll();

        if (count($domesticSurveys) === 0) {
            // create one
            $domesticSurvey = (new Survey())
                ->setIsNorthernIreland(false)
                ->setRegistrationMark("AA19PPP")
                ->setReminderState(Survey::REMINDER_STATE_NOT_WANTED)
                ->setPasscode("1234")
            ;
            $em = $this->getDoctrine()->getManager();
            $vehicle = (new Vehicle())->setRegistrationMark($domesticSurvey->getRegistrationMark());
            $surveyResponse = (new SurveyResponse())->setSurvey($domesticSurvey)->setVehicle($vehicle);
            $em->persist($surveyResponse);
            $em->flush();

            // start wizard
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }

        // take first one
        $domesticSurvey = array_pop($domesticSurveys);

        // show summary
        return $this->render('domestic_survey/summary.html.twig', [
            'domesticSurvey' => $domesticSurvey,
        ]);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $dayNumber
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @Route("/day-{dayNumber}")
     */
    public function viewDay(EntityManagerInterface $entityManager, Request $request, $dayNumber)
    {
        // load the survey
        $survey = $entityManager->getRepository(Survey::class)->findLatestSurveyForTesting();

        // fetch day for survey/dayNumber
        $day = $entityManager->getRepository(Day::class)->findOneBy(['day' => $dayNumber, 'response' => $survey->getResponse()]);

        if ($day) {
            // if there is already a day, show the day summary screen
            return $this->render('domestic_survey/day-summary.html.twig', [
                'day' => $day,
            ]);
        } else {
            // no day, so lets ask what type it is, and create one
            $day = (new Day())
                ->setDay($dayNumber)
                ->setResponse($survey->getResponse())
            ;

            /** @var Form $form */
            $form = $this->createForm(NumberOfStopsType::class, $day);

            $form->handleRequest($request);
            $clickedButton = $form->getClickedButton();

            if ($clickedButton && $clickedButton->getName() === 'continue') {
                $entityManager->persist($day);
                $entityManager->flush();

                // redirect back here... the summary screen
                return $this->redirectToRoute('app_domesticsurvey_index_viewday', ['dayNumber' => $dayNumber]);
            }

            // show the form/template
            return $this->render('domestic_survey/create-day.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    }
}
