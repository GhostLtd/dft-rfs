<?php

namespace App\Controller\DomesticSurvey;

use App\Entity\Domestic\Day;
use App\Entity\PasscodeUser;
use App\Form\DomesticSurvey\CreateDay\NumberOfStopsType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/domestic-survey/day-{dayNumber}", requirements={"dayNumber"="[1-7]"})
 * @Security("is_granted('EDIT', user.getDomesticSurvey())")
 */
class DayController extends AbstractController
{
    /**
     * @Route("")
     */
    public function view(EntityManagerInterface $entityManager, Request $request, $dayNumber): Response
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        // load the survey
        $survey = $user->getDomesticSurvey();

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
     * @Route("/add")
     */
    public function add(EntityManagerInterface $entityManager, Request $request, $dayNumber): Response
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        // load the survey
        $survey = $user->getDomesticSurvey();

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

            if ($day->getHasMoreThanFiveStops()) {
                return $this->redirectToRoute('app_domesticsurvey_daysummary_start', ['dayNumber' => $dayNumber]);
            } else {
                return $this->redirectToRoute('app_domesticsurvey_daystop_start', ['dayNumber' => $dayNumber]);
            }

            // redirect back here... the summary screen
//            return $this->redirectToRoute('app_domesticsurvey_day_view', ['dayNumber' => $dayNumber]);
        }

        // show the form/template
        return $this->render('domestic_survey/create-day.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
