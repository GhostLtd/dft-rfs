<?php

namespace App\Controller\Admin\International;

use App\Entity\International\SurveyNote;
use App\Entity\International\Survey;
use App\Entity\SurveyStateInterface;
use App\Repository\AuditLog\AuditLogRepository;
use App\Repository\PreEnquiry\PreEnquiryRepository;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\AddNotesHelper;
use App\Utility\ConfirmAction\Common\Admin\DeleteSurveyNoteConfirmAction;
use App\Utility\International\PdfHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/irhs/surveys/{surveyId}')]
class SurveyViewController extends AbstractController
{
    public function __construct(
        protected AddNotesHelper     $addNotesHelper,
        protected AuditLogRepository $auditLogRepository,
        protected PdfHelper          $pdfHelper,
    ) {}

    #[Route(path: '', name: SurveyController::VIEW_ROUTE)]
    public function view(
        #[MapEntity(expr: "repository.findWithVehiclesAndTrips(surveyId)")]
        Survey               $survey,
        Request              $request,
        PreEnquiryRepository $preEnquiryRepository,
    ): Response
    {
        $addNotesForm = $this->addNotesHelper->getForm(SurveyNote::class);
        if ($this->addNotesHelper->formSubmittedAndProcessed($request, $survey, $addNotesForm)) {
            return new RedirectResponse($this->addNotesHelper->addNotesTabAnchor($this->generateUrl('admin_international_survey_view', ['surveyId' => $survey->getId()])));
        }

        return $this->render('admin/international/surveys/view.html.twig', [
            'addNoteForm' => $addNotesForm,
            'approvedBy' => in_array($survey->getState(), [
                SurveyStateInterface::STATE_INVITATION_SENT,
                SurveyStateInterface::STATE_NEW,
                SurveyStateInterface::STATE_IN_PROGRESS,
                SurveyStateInterface::STATE_REJECTED,
                SurveyStateInterface::STATE_CLOSED,
            ]) ? false : $this->auditLogRepository->getApprovedBy($survey),
            'auditLogs' => $this->auditLogRepository->getLogs($survey->getId(), Survey::class),
            'pdfs' => $this->pdfHelper->getExistingSurveyPDFs($survey),
            'qualityAssuredBy' => $survey->getQualityAssured() ?
                $this->auditLogRepository->getQualityAssuredBy($survey) :
                false,
            'relatedPreEnquiryIds' => $preEnquiryRepository->findPreEnquiryIdsByCompanyName($survey->getCompany()->getBusinessName()),
            'survey' => $survey,
        ]);
    }

    #[Route(path: '/notes/{note}/delete', name: SurveyController::DELETE_NOTE_ROUTE)]
    #[Template('admin/notes/delete.html.twig')]
    #[IsGranted(AdminSurveyVoter::EDIT_NOTES, subject: 'survey')]
    public function deleteNote(
        Request                       $request,
        DeleteSurveyNoteConfirmAction $deleteSurveyNoteConfirmAction,
        #[MapEntity(expr: "repository.findWithVehiclesAndTrips(surveyId)")]
        Survey                        $survey,
        SurveyNote                    $note
    ): Response|array
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
                fn() => $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]) . "#tab-notes"
            );
    }
}
