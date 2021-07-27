<?php

namespace App\Controller\DomesticSurvey;

use App\Entity\Domestic\Day;
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
    use SurveyHelperTrait;

    public const ROUTE_PREFIX = 'app_domesticsurvey_day_';

    public const ADD_ROUTE = self::ROUTE_PREFIX . 'add';
    public const VIEW_ROUTE = self::ROUTE_PREFIX . 'view';

    /**
     * @Route("", name=self::VIEW_ROUTE)
     */
    public function view(EntityManagerInterface $entityManager, string $dayNumber): Response
    {
        $survey = $this->getSurvey();

        // fetch day for survey/dayNumber
        $day = $entityManager->getRepository(Day::class)->findOneBy(['number' => $dayNumber, 'response' => $survey->getResponse()]);

        if ($day) {
            // if there is already a day, show the day summary screen
            return $this->render('domestic_survey/day-summary.html.twig', [
                'day' => $day,
            ]);
        } else {
            // no day, redirect to add
            return $this->redirectToRoute(DayController::ADD_ROUTE, ['dayNumber' => $dayNumber]);
        }
    }

    /**
     * @Route("/add", name=self::ADD_ROUTE)
     */
    public function add(Request $request, $dayNumber): Response
    {
        /** @var Form $form */
        $form = $this->createForm(NumberOfStopsType::class, null, ['day_number' => $dayNumber]);

        $form->handleRequest($request);
        $clickedButton = $form->getClickedButton();

        if ($form->isSubmitted()) {
            if ($clickedButton->getName() === 'cancel') {
                return $this->redirectToRoute(IndexController::SUMMARY_ROUTE);
            }
            if ($form->isValid()) {
                $data = $form->getData();
                $hasMoreThanFiveStops = $data['hasMoreThanFiveStops'];

                if ($hasMoreThanFiveStops === true) {
                    return $this->redirectToRoute('app_domesticsurvey_daysummary_start', ['dayNumber' => $dayNumber]);
                } else if ($hasMoreThanFiveStops === false) {
                    return $this->redirectToRoute('app_domesticsurvey_daystop_start', ['dayNumber' => $dayNumber]);
                }
            }
        }

        // show the form/template
        return $this->render('domestic_survey/create-day.html.twig', [
            'form' => $form->createView(),
            'dayNumber' => $dayNumber,
        ]);
    }
}
