<?php


namespace App\Controller\Admin;


use App\Entity\SurveyInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class SurveyAddNoteController extends AbstractController
{
    public function addNotePostAction(Request $request, EntityManagerInterface $entityManager, SurveyInterface $survey, FormInterface $addNoteForm, string $viewSurveyRoute, string $viewSurveyTemplate)
    {
        $addNoteForm->handleRequest($request);
        if (!$addNoteForm->isSubmitted()) {
            return $this->redirect(self::addNotesTabAnchor($this->generateUrl($viewSurveyRoute, ['surveyId' => $survey->getId()])));
        }
        if ($addNoteForm->isValid()) {
            // save the note
            $survey->addNote($note = $addNoteForm->getData());
            $entityManager->persist($note);
            $entityManager->flush();

            // redirect to view url (on notes tab)
            return $this->redirect(self::addNotesTabAnchor($this->generateUrl($viewSurveyRoute, ['surveyId' => $survey->getId()])));
        }
        // render view
        return $this->render($viewSurveyTemplate, [
            'survey' => $survey,
            'addNoteForm' => $addNoteForm->createView(),
        ]);
    }

    public static function addNotesTabAnchor(string $url)
    {
        return $url . '#tab-notes';
    }
}