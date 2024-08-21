<?php

namespace App\Controller\DomesticSurvey;

use App\Attribute\Redirect;
use App\Form\ConfirmActionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Redirect("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())", route: "app_domesticsurvey_completed")]
class DirectCloseController extends AbstractController
{
    use SurveyHelperTrait;

    public function __construct(protected EntityManagerInterface $entityManager, protected WorkflowInterface $domesticSurveyStateMachine)
    {
    }

    #[IsGranted(new Expression("is_granted('CLOSE_SURVEY_EXEMPT', user.getDomesticSurvey())"))]
    #[Route(path: '/domestic-survey/close-exempt', name: 'app_domesticsurvey_close_exempt')]
    public function exemptClose(Request $request): Response
    {
        return $this->handleSurveyConfirmationAndClose($request, 'domestic.closing-exempt', 'reject_exempt');
    }

    #[IsGranted(new Expression("is_granted('CLOSE_SURVEY_NOT_IN_POSSESSION', user.getDomesticSurvey())"))]
    #[Route(path: '/domestic-survey/close-not-in-possession', name: 'app_domesticsurvey_close_not_in_possession')]
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
            'form' => $form,
            'subject' => [
                'survey' => $survey,
            ],
        ]);
    }
}
