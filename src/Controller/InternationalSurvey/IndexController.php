<?php

namespace App\Controller\InternationalSurvey;

use App\Form\InternationalSurvey\SubmitSurveyType;
use App\Repository\International\SurveyRepository;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class IndexController extends AbstractController
{
    use SurveyHelperTrait;

    public const SUMMARY_ROUTE = 'app_internationalsurvey_summary';

    protected $surveyRepo;

    protected $session;

    public function __construct(SurveyRepository $surveyRepo, SessionInterface $session)
    {
        $this->surveyRepo = $surveyRepo;
        $this->session = $session;
    }

    /**
     * @Route("/international-survey", name=self::SUMMARY_ROUTE)
     */
    public function index(UserInterface $user, Request $request, EntityManagerInterface $entityManager) {
        // TODO: Replace this with a better session wizards clearing mechanism
        $this->session->remove('wizard.' . InitialDetailsController::class);

        $survey = $this->getSurvey($user);

        if ($survey->getSubmissionDate()) {
            return $this->render('international_survey/thanks.html.twig');
        }

        if (!$survey->getResponse()) {
            return $this->redirectToRoute(InitialDetailsController::WIZARD_ROUTE, ['state' => InitialDetailsState::STATE_INTRODUCTION]);
        }

        $form = $this->createForm(SubmitSurveyType::class);

        if ($survey->getResponse()->canSubmit() && $request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $button = $form->get('submit');
            if ($button instanceof SubmitButton && $button->isClicked()) {
                $survey->setSubmissionDate(new DateTime());
                $entityManager->flush();

                return $this->redirectToRoute(self::SUMMARY_ROUTE);
            }
        }

        return $this->render('international_survey/summary.html.twig', [
            'survey' => $survey,
            'form' => $form->createView(),
        ]);
    }
}