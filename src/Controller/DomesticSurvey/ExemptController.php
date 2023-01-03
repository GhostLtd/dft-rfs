<?php

namespace App\Controller\DomesticSurvey;

use App\Annotation\Redirect;
use App\Form\ConfirmActionType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class ExemptController extends AbstractController
{
    use SurveyHelperTrait;

    /**
     * @Route("/domestic-survey/exempt", name="app_domesticsurvey_exempt")
     * @Redirect("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())", route="app_domesticsurvey_completed")
     * @Security("is_granted('CLOSE_SURVEY_EXEMPT', user.getDomesticSurvey())", )
     */
    public function index(EntityManagerInterface $entityManager, Request $request, WorkflowInterface $domesticSurveyStateMachine): Response
    {
        $survey = $this->getSurvey();
        $form = $this->createForm(ConfirmActionType::class, null, [
            'translation_key_prefix' => 'domestic.closing-exempt',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $redirectResponse = new RedirectResponse($this->generateUrl('app_domesticsurvey_summary'));

            $confirmButton = $form->get('confirm');
            if ($confirmButton instanceof SubmitButton && $confirmButton->isClicked()) {
                $domesticSurveyStateMachine->apply($survey, 'reject_exempt');
                $entityManager->flush();
            }

            return $redirectResponse;
        }

        return $this->render('domestic_survey/closing_details/confirm.html.twig', [
            'form' => $form->createView(),
            'subject' => [
                'survey' => $survey,
            ],
        ]);
    }
}