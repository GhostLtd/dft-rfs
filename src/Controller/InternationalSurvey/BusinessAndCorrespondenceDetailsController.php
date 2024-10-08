<?php

namespace App\Controller\InternationalSurvey;

use App\Form\InternationalSurvey\ConfirmationType;
use App\Repository\International\SurveyRepository;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
class BusinessAndCorrespondenceDetailsController extends AbstractController
{
    use SurveyHelperTrait;

    public const SUMMARY_ROUTE = 'app_internationalsurvey_correspondence_and_business_details';

    public function __construct(protected SurveyRepository $surveyRepo)
    {
    }

    #[Route(path: '/international-survey/correspondence-and-business-details', name: self::SUMMARY_ROUTE)]
    public function index(Request $request, EntityManagerInterface $entityManager, WorkflowInterface $internationalSurveyStateMachine): Response
    {
        $survey = $this->getSurvey();

        if (!$survey->isInitialDetailsComplete()) {
            return $this->redirectToRoute(InitialDetailsController::WIZARD_ROUTE, ['state' => InitialDetailsState::STATE_INTRODUCTION]);
        }

        $response = $survey->getResponse();
        $detailsComplete = $response->isInitialDetailsSignedOff();

        $canSubmitAsNoLongerActive = $response->isNoLongerActive();
        if ($canSubmitAsNoLongerActive) {
            $submitLabel = 'international.buttons.submit-survey';
        } elseif (!$detailsComplete) {
            $submitLabel = 'international.buttons.add-vehicles';
        } else {
            $submitLabel = null;
        }

        if ($submitLabel) {
            $form = $this->createForm(ConfirmationType::class, null, [
                'label' => $submitLabel,
            ]);
        } else {
            $form = null;
        }

        if ($form && $request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $button = $form->get('submit');
            if ($button instanceof SubmitButton && $button->isClicked()) {
                if ($canSubmitAsNoLongerActive) {
                    // Not trading or domestic journeys only
                    $response->setInitialDetailsSignedOff(true);

                    if ($internationalSurveyStateMachine->can($survey, 'complete'))
                    {
                        $internationalSurveyStateMachine->apply($survey, 'complete');
                    }
                } elseif (!$detailsComplete) {
                    // Confirming that correspondence + business details look ok
                    $response->setInitialDetailsSignedOff(true);
                }
            }

            $entityManager->flush();
            return $this->redirectToRoute(IndexController::SUMMARY_ROUTE);
        }

        return $this->render('international_survey/correspondence-and-business-details.html.twig', [
            'survey' => $survey,
            'detailsComplete' => $detailsComplete,
            'form' => $form,
        ]);
    }
}
