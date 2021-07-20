<?php

namespace App\Controller\Admin\Domestic;

use App\Controller\Admin\SurveyAddNoteController;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNote;
use App\Entity\SurveyInterface;
use App\Form\NoteType;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Common\Admin\DeleteSurveyNoteConfirmAction;
use App\Utility\Domestic\PdfHelper;
use App\Repository\AuditLog\AuditLogRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/surveys/{surveyId}")
 * @Entity("survey", expr="repository.find(surveyId)")
 */
class SurveyViewController extends AbstractController
{
    private PdfHelper $pdfHelper;
    private AuditLogRepository $logRepository;

    public function __construct(PdfHelper $pdfHelper, AuditLogRepository $logRepository)
    {
        $this->pdfHelper = $pdfHelper;
        $this->logRepository = $logRepository;
    }

    /**
     * @Route("", name=SurveyController::VIEW_ROUTE)
     */
    public function viewDetails(Survey $survey): Response
    {
        $addNoteForm = $this->getAddNoteForm($survey);

        return $this->render('admin/domestic/surveys/view.html.twig', array_merge(
            $this->getSurveyViewData($survey),
            [
                'survey' => $survey,
                'addNoteForm' => $addNoteForm->createView(),
            ]
        ));
    }

    /**
     * @Route("/add-note", name=SurveyController::ADD_NOTE_ROUTE)
     * @IsGranted(AdminSurveyVoter::EDIT_NOTES, subject="survey")
     */
    public function addNote(Survey $survey)
    {
        $addNoteForm = $this->getAddNoteForm($survey);
        return $this->forward(SurveyAddNoteController::class . '::addNotePostAction', [
            'survey' => $survey,
            'addNoteForm' => $addNoteForm,
            'viewSurveyRoute' => SurveyController::VIEW_ROUTE,
            'viewSurveyRouteParams' => ['surveyId' => $survey->getId()],
            'viewSurveyTemplate' => 'admin/domestic/surveys/view.html.twig',
            'additionalViewData' => $this->getSurveyViewData($survey),
        ]);
    }

    protected function getSurveyViewData(Survey $survey): array
    {
        return [
            'survey' => $survey,
            'pdfs' => $this->pdfHelper->getExistingSurveyPDFs($survey),
            'auditLogs' => $this->logRepository->getLogs($survey->getId(), Survey::class),
            'approvedBy' => in_array($survey->getState(), [
                SurveyInterface::STATE_INVITATION_SENT,
                SurveyInterface::STATE_NEW,
                SurveyInterface::STATE_IN_PROGRESS,
                SurveyInterface::STATE_REJECTED,
                SurveyInterface::STATE_CLOSED,
            ]) ? false : $this->logRepository->getApprovedBy($survey),
            'qualityAssuredBy' => $survey->getQualityAssured() ?
                $this->logRepository->getQualityAssuredBy($survey) :
                false,
        ];
    }

    /**
     * @Route("/notes/{note}/delete", name=SurveyController::DELETE_NOTE_ROUTE)
     * @Template("admin/notes/delete.html.twig")
     * @IsGranted(AdminSurveyVoter::EDIT_NOTES, subject="survey")
     */
    public function deleteNote(Request $request, DeleteSurveyNoteConfirmAction $deleteSurveyNoteConfirmAction, Survey $survey, SurveyNote $note)
    {
        // check note is part of survey
        if ($note->getSurvey()->getId() !== $survey->getId()) {
            throw new BadRequestHttpException('Given Note is not a member of given Survey');
        }

        return $deleteSurveyNoteConfirmAction
            ->setSubject($note)
            ->setExtraViewData([
                'deleteRoute' => SurveyController::DELETE_NOTE_ROUTE,
                'deleteParams' => ['surveyId' => $survey->getId()],
            ])
            ->controller(
                $request,
                fn() => $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()])."#tab-notes"
            );
    }

    protected function getAddNoteForm(SurveyInterface $survey)
    {
        return $this->createForm(NoteType::class, new SurveyNote(), [
            'action' => SurveyAddNoteController::addNotesTabAnchor($this->generateUrl(SurveyController::ADD_NOTE_ROUTE, ['surveyId' => $survey->getId()])),
        ]);
    }
}