<?php

namespace App\Controller\Admin\RoRo;

use App\Entity\RoRo\Survey;
use App\Entity\RoRo\SurveyNote;
use App\Repository\AuditLog\AuditLogRepository;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Common\Admin\DeleteSurveyNoteConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/roro/surveys/{surveyId}", name: "admin_roro_surveys_note_")]
#[Entity("survey", expr: "repository.find(surveyId)")]
#[IsGranted(AdminSurveyVoter::EDIT_NOTES, subject: "survey")]
class SurveyNoteController extends AbstractController
{
    public function __construct(
        protected AuditLogRepository $auditLogRepository,
    ) {}

    #[Route("/notes/{note}/delete", name: "delete")]
    #[Template("admin/notes/delete.html.twig")]
    public function deleteNote(Request $request, DeleteSurveyNoteConfirmAction $deleteSurveyNoteConfirmAction, Survey $survey, SurveyNote $note): Response|array
    {
        // check note is part of survey
        if ($note->getSurvey()->getId() !== $survey->getId()) {
            throw new BadRequestHttpException('Given Note is not a member of given Survey');
        }

        return $deleteSurveyNoteConfirmAction
            ->setSubject($note)
            ->setExtraViewData([
                'deleteRoute' => 'admin_roro_surveys_delete_note',
                'deleteParams' => ['surveyId' => $survey->getId()],
            ])
            ->controller(
                $request,
                fn() => $this->generateUrl('admin_roro_surveys_view', ['surveyId' => $survey->getId()])."#tab-notes"
            );
    }
}