<?php

namespace App\Controller;

use App\Entity\PasscodeUser;
use App\Form\FeedbackType;
use App\Security\Voter\SurveyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/survey-feedback', name: 'survey_feedback_')]
class FeedbackController extends AbstractController
{
    #[Route(path: '', name: 'form')]
    #[Template('feedback/form.html.twig')]
    public function feedback(Request $request, EntityManagerInterface $entityManager): array|RedirectResponse
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $survey = $user->getSurvey();

        // Do we already have feedback for this user/survey?
        if (!$this->isGranted(SurveyVoter::PROVIDE_FEEDBACK, $survey)) {
            return $this->redirectToRoute("survey_feedback_thanks");
        }

        $form = $this->createForm(FeedbackType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $cancelButton = $form->get('cancel');
            if ($cancelButton instanceof SubmitButton && $cancelButton->isClicked()) {
                // Bounce to the index and that'll forward us to the appropriate page, no matter the survey type
                return $this->redirectToRoute("app_home_index");
            }

            if ($form->isValid()) {
                $survey->setFeedback($form->getData());
                $entityManager->flush();
                return $this->redirectToRoute("survey_feedback_thanks");
            }
        }
        return [
            'form' => $form->createView(),
        ];
    }

    #[Route(path: '/thanks', name: 'thanks')]
    #[Template('feedback/thanks.html.twig')]
    public function thanks(): void
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $survey = $user->getSurvey();
        $this->denyAccessUnlessGranted(SurveyVoter::VIEW_FEEDBACK_SUMMARY, $survey);
    }
}
