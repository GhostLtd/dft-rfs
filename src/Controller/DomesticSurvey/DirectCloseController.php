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

/**
 * @Redirect("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())", route="app_domesticsurvey_completed")
 */
class DirectCloseController extends AbstractController
{
    use SurveyHelperTrait;

    protected EntityManagerInterface $entityManager;
    protected WorkflowInterface $domesticSurveyStateMachine;

    public function __construct(EntityManagerInterface $entityManager, WorkflowInterface $domesticSurveyStateMachine)
    {
        $this->entityManager = $entityManager;
        $this->domesticSurveyStateMachine = $domesticSurveyStateMachine;
    }

    /**
     * @Route("/domestic-survey/close-exempt", name="app_domesticsurvey_close_exempt")
     * @Security("is_granted('CLOSE_SURVEY_EXEMPT', user.getDomesticSurvey())", )
     */
    public function exemptClose(Request $request): Response
    {
        return $this->handleSurveyConfirmationAndClose($request, 'domestic.closing-exempt', 'reject_exempt');
    }

    /**
     * @Route("/domestic-survey/close-not-in-possession", name="app_domesticsurvey_close_not_in_possession")
     * @Security("is_granted('CLOSE_SURVEY_NOT_IN_POSSESSION', user.getDomesticSurvey())", )
     */
    public function notInPossessionClose(Request $request): Response
    {
        return $this->handleSurveyConfirmationAndClose($request, 'domestic.closing-not-in-possession', 'reject_not_in_possession');
    }

    protected function handleSurveyConfirmationAndClose(Request $request, string $translationKeyPrefix, string $transition): Response
    {
        $survey = $this->getSurvey();
        $form = $this->createForm(ConfirmActionType::class, null, [
            'translation_key_prefix' => $translationKeyPrefix,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $redirectResponse = new RedirectResponse($this->generateUrl('app_domesticsurvey_summary'));

            $confirmButton = $form->get('confirm');
            if ($confirmButton instanceof SubmitButton && $confirmButton->isClicked()) {
                $this->domesticSurveyStateMachine->apply($survey, $transition);
                $this->entityManager->flush();
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