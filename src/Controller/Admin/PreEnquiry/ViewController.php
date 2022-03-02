<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Controller\Admin\SurveyAddNoteController;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryNote;
use App\Entity\SurveyInterface;
use App\Form\NoteType;
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

/**
 * @Route("/pre-enquiry/{preEnquiryId}/view")
 * @Entity("preEnquiry", expr="repository.find(preEnquiryId)")
 */
class ViewController extends AbstractController
{
    private AuditLogRepository $logRepository;

    public function __construct(AuditLogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * @Route("", name=EditController::VIEW_ROUTE)
     */
    public function view(PreEnquiry $preEnquiry): Response
    {
        $addNoteForm = $this->getAddNoteForm($preEnquiry);

        return $this->render('admin/pre_enquiry/view.html.twig', array_merge(
            $this->getPreEnquiryViewData($preEnquiry),
            [
                'preEnquiry' => $preEnquiry,
                'addNoteForm' => $addNoteForm->createView(),
            ]
        ));
    }

    /**
     * @Route("/add-note", name=EditController::ADD_NOTE_ROUTE)
     * @IsGranted(AdminSurveyVoter::EDIT_NOTES, subject="preEnquiry")
     */
    public function addNote(PreEnquiry $preEnquiry)
    {
        $addNoteForm = $this->getAddNoteForm($preEnquiry);
        return $this->forward(SurveyAddNoteController::class . '::addNotePostAction', [
            'survey' => $preEnquiry,
            'addNoteForm' => $addNoteForm,
            'viewSurveyRoute' => EditController::VIEW_ROUTE,
            'viewSurveyRouteParams' => ['preEnquiryId' => $preEnquiry->getId()],
            'viewSurveyTemplate' => 'admin/pre_enquiry/view.html.twig',
            'additionalViewData' => $this->getPreEnquiryViewData($preEnquiry),
        ]);
    }

    protected function getPreEnquiryViewData(PreEnquiry $preEnquiry): array
    {
        return [
//            'pdfs' => $this->pdfHelper->getExistingSurveyPDFs($preEnquiry),
            'preEnquiry' => $preEnquiry,
            'auditLogs' => $this->logRepository->getLogs($preEnquiry->getId(), PreEnquiry::class),
            'approvedBy' => in_array($preEnquiry->getState(), [
                SurveyInterface::STATE_INVITATION_SENT,
                SurveyInterface::STATE_NEW,
                SurveyInterface::STATE_IN_PROGRESS,
                SurveyInterface::STATE_REJECTED,
                SurveyInterface::STATE_CLOSED,
            ]) ? false : $this->logRepository->getApprovedBy($preEnquiry),
        ];
    }

    protected function getAddNoteForm(SurveyInterface $preEnquiry)
    {
        return $this->createForm(NoteType::class, new PreEnquiryNote(), [
            'action' => SurveyAddNoteController::addNotesTabAnchor($this->generateUrl(EditController::ADD_NOTE_ROUTE, ['preEnquiryId' => $preEnquiry->getId()])),
        ]);
    }

    /**
     * @Route("/notes/{note}/delete", name=EditController::DELETE_NOTE_ROUTE)
     * @Template("admin/notes/delete.html.twig")
     * @IsGranted(AdminSurveyVoter::EDIT_NOTES, subject="preEnquiry")
     */
    public function deleteNote(Request $request, DeleteSurveyNoteConfirmAction $deleteSurveyNoteConfirmAction, PreEnquiry $preEnquiry, PreEnquiryNote $note)
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