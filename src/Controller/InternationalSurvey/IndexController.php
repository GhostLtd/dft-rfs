<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Survey;
use App\Repository\International\SurveyRepository;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class IndexController extends AbstractController
{
    use SurveyHelperTrait;

    public const COMPLETED_ROUTE = 'app_internationalsurvey_completed';
    public const SUMMARY_ROUTE = 'app_internationalsurvey_summary';

    protected SurveyRepository $surveyRepo;

    public function __construct(SurveyRepository $surveyRepo)
    {
        $this->surveyRepo = $surveyRepo;
    }

    /**
     * @Route("/international-survey/completed", name=self::COMPLETED_ROUTE)
     * @Security("is_granted('VIEW_SUBMISSION_SUMMARY', user.getInternationalSurvey())")
     */
    public function completed(): Response {
        return $this->render('international_survey/thanks.html.twig');
    }

    /**
     * @Route("/international-survey", name=self::SUMMARY_ROUTE)
     */
    public function index(UserInterface $user): Response {
        $survey = $this->getSurvey($user);

        if ($survey->getState() === Survey::STATE_CLOSED) {
            return $this->redirectToRoute(self::COMPLETED_ROUTE);
        }

        if (!$survey->isInitialDetailsComplete()) {
            return $this->redirectToRoute(InitialDetailsController::WIZARD_ROUTE, ['state' => InitialDetailsState::STATE_INTRODUCTION]);
        }

        $response = $survey->getResponse();

        if (!$response->isInitialDetailsSignedOff()) {
            return $this->redirectToRoute(BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE);
        }

        $vehicles = $response->getVehicles();

        return $this->render('international_survey/summary.html.twig', [
            'response' => $response,
            'vehicles' => $vehicles,
        ]);
    }
}