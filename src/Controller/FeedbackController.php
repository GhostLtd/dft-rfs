<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Entity\PasscodeUser;
use App\Form\FeedbackType;
use App\Security\Voter\SurveyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/survey-feedback", name="survey_feedback_")
 */
class FeedbackController extends AbstractController
{
    /**
     * @Route("", name="form")
     * @Template("feedback/form.html.twig")
     */
    public function feedback(Request $request, EntityManagerInterface $entityManager)
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
        if ($form->isSubmitted() && $form->isValid()) {
            $survey->setFeedback($form->getData());
            $entityManager->flush();
            return $this->redirectToRoute("survey_feedback_thanks");
        }
        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/thanks", name="thanks")
     * @Template("feedback/thanks.html.twig")
     */
    public function thanks()
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $survey = $user->getSurvey();
        $this->denyAccessUnlessGranted(SurveyVoter::VIEW_FEEDBACK_SUMMARY, $survey);
    }
}