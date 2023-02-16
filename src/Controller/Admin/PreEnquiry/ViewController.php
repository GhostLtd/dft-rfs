<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryNote;
use App\Entity\SurveyStateInterface;
use App\Repository\AuditLog\AuditLogRepository;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\AddNotesHelper;
use App\Utility\ConfirmAction\Common\Admin\DeleteSurveyNoteConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/pre-enquiry/{preEnquiryId}/view")
 * @Entity("preEnquiry", expr="repository.find(preEnquiryId)")
 */
class ViewController extends AbstractController
{
    public function __construct(
        protected AuditLogRepository $auditLogRepository,
        protected AddNotesHelper $addNotesHelper,
    ) {}

    /**
     * @Route("", name=EditController::VIEW_ROUTE)
     */
    public function view(PreEnquiry $preEnquiry, Request $request): Response
    {
        $addNotesForm = $this->addNotesHelper->getForm(PreEnquiryNote::class);
        if ($this->addNotesHelper->formSubmittedAndProcessed($request, $preEnquiry, $addNotesForm)) {
            return new RedirectResponse($this->addNotesHelper->addNotesTabAnchor($this->generateUrl('admin_preenquiry_view', ['preEnquiryId' => $preEnquiry->getId()])));
        }

        return $this->render('admin/pre_enquiry/view.html.twig', [
            'addNoteForm' => $addNotesForm->createView(),
            'approvedBy' => in_array($preEnquiry->getState(), [
                SurveyStateInterface::STATE_INVITATION_SENT,
                SurveyStateInterface::STATE_NEW,
                SurveyStateInterface::STATE_IN_PROGRESS,
                SurveyStateInterface::STATE_REJECTED,
                SurveyStateInterface::STATE_CLOSED,
            ]) ? false : $this->auditLogRepository->getApprovedBy($preEnquiry),
            'auditLogs' => $this->auditLogRepository->getLogs($preEnquiry->getId(), PreEnquiry::class),
//          'pdfs' => $this->pdfHelper->getExistingSurveyPDFs($preEnquiry),
            'preEnquiry' => $preEnquiry,
        ]);
    }

    /**
     * @Route("/notes/{note}/delete", name=EditController::DELETE_NOTE_ROUTE)
     * @Template("admin/notes/delete.html.twig")
     * @IsGranted(AdminSurveyVoter::EDIT_NOTES, subject="preEnquiry")
     */
    public function deleteNote(Request $request, DeleteSurveyNoteConfirmAction $deleteSurveyNoteConfirmAction, PreEnquiry $preEnquiry, PreEnquiryNote $note): Response|array
    {
        if ($note->getPreEnquiry()->getId() !== $preEnquiry->getId()) {
            throw new BadRequestHttpException('Given Note is not a member of given Pre-enquiry');
        }

        return $deleteSurveyNoteConfirmAction
            ->setSubject($note)
            ->setExtraViewData([
                'deleteRoute' => EditController::DELETE_NOTE_ROUTE,
                'deleteParams' => ['preEnquiryId' => $preEnquiry->getId()],
            ])
            ->controller(
                $request,
                fn() => $this->generateUrl(EditController::VIEW_ROUTE, ['preEnquiryId' => $preEnquiry->getId()])."#tab-notes"
            );
    }
}