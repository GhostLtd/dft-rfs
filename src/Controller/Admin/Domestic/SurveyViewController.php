<?php


namespace App\Controller\Admin\Domestic;


use App\Controller\Admin\SurveyAddNoteController;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNote;
use App\Entity\SurveyInterface;
use App\Form\NoteType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/surveys/{surveyId}")
 * @Entity("survey", expr="repository.find(surveyId)")
 */
class SurveyViewController extends AbstractController
{
    /**
     * @Route("", name=SurveyController::VIEW_ROUTE)
     */
    public function viewDetails(Survey $survey): Response
    {
        $addNoteForm = $this->getAddNoteForm($survey);
        return $this->render('admin/domestic/surveys/view.html.twig', [
            'survey' => $survey,
            'addNoteForm' => $addNoteForm->createView(),
        ]);
    }

    /**
     * @Route("/add-note", name=SurveyController::ADD_NOTE_ROUTE)
     */
    public function addNote(Request $request, EntityManagerInterface $entityManager, Survey $survey)
    {
        $addNoteForm = $this->getAddNoteForm($survey);
        return $this->forward(SurveyAddNoteController::class . '::addNotePostAction', [
            'survey' => $survey,
            'addNoteForm' => $addNoteForm,
            'viewSurveyRoute' => SurveyController::VIEW_ROUTE,
            'viewSurveyTemplate' => 'admin/domestic/surveys/view.html.twig',
        ]);
    }

    protected function getAddNoteForm(SurveyInterface $survey)
    {
        return $this->createForm(NoteType::class, new SurveyNote(), [
            'action' => SurveyAddNoteController::addNotesTabAnchor($this->generateUrl(SurveyController::ADD_NOTE_ROUTE, ['surveyId' => $survey->getId()])),
        ]);
    }
}