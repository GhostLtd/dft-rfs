<?php

namespace App\Controller\Admin\International;

use App\Controller\Admin\SurveyAddNoteController;
use App\Entity\BlameLog\BlameLog;
use App\Entity\International\SurveyNote;
use App\Entity\International\Survey;
use App\Entity\SurveyInterface;
use App\Form\NoteType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs/surveys/{surveyId}")
 * @Entity("survey", expr="repository.findWithVehiclesAndTrips(surveyId)")
 */
class SurveyViewController extends AbstractController
{
    /**
     * @Route("", name=SurveyController::VIEW_ROUTE)
     */
    public function view(Survey $survey, EntityManagerInterface $entityManager): Response
    {
        $addNoteForm = $this->getAddNoteForm($survey);
        $blameLogRepository = $entityManager->getRepository(BlameLog::class);

        return $this->render('admin/international/surveys/view.html.twig', [
            'survey' => $survey,
            'addNoteForm' => $addNoteForm->createView(),
            'approvedBy' => in_array($survey->getState(), [
                SurveyInterface::STATE_INVITATION_SENT,
                SurveyInterface::STATE_NEW,
                SurveyInterface::STATE_IN_PROGRESS,
                SurveyInterface::STATE_REJECTED,
                SurveyInterface::STATE_CLOSED,
            ]) ? false : $blameLogRepository->getApprovingUsername($survey),
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
            'viewSurveyTemplate' => 'admin/international/surveys/view.html.twig',
        ]);
    }

    protected function getAddNoteForm(SurveyInterface $survey)
    {
        return $this->createForm(NoteType::class, new SurveyNote(), [
            'action' => SurveyAddNoteController::addNotesTabAnchor($this->generateUrl(SurveyController::ADD_NOTE_ROUTE, ['surveyId' => $survey->getId()])),
        ]);
    }
}