<?php

namespace App\Controller\Admin\International;

use App\Controller\Admin\SurveyAddNoteController;
use App\Entity\International\SurveyNote;
use App\Entity\International\Survey;
use App\Entity\SurveyInterface;
use App\Form\NoteType;
use App\Repository\AuditLog\AuditLogRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function view(Survey $survey, AuditLogRepository $logRepository): Response
    {
        $addNoteForm = $this->getAddNoteForm($survey);

        return $this->render('admin/international/surveys/view.html.twig', [
            'survey' => $survey,
            'addNoteForm' => $addNoteForm->createView(),
            'auditLogs' => $logRepository->getLogs($survey->getId(), Survey::class),
            'approvedBy' => in_array($survey->getState(), [
                SurveyInterface::STATE_INVITED_USER,
                SurveyInterface::STATE_NEW,
                SurveyInterface::STATE_IN_PROGRESS,
                SurveyInterface::STATE_REJECTED,
                SurveyInterface::STATE_CLOSED,
            ]) ? false : $logRepository->getApprovedBy($survey),
        ]);
    }

    /**
     * @Route("/add-note", name=SurveyController::ADD_NOTE_ROUTE)
     */
    public function addNote(Survey $survey)
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