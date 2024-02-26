<?php

namespace App\Controller\Admin\RoRo;

use App\Utility\AddNotesHelper;
use App\Entity\RoRo\Survey;
use App\Entity\RoRo\SurveyNote;
use App\Entity\SurveyStateInterface;
use App\Repository\AuditLog\AuditLogRepository;
use App\Utility\RoRo\VehicleCountHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SurveyViewController extends AbstractController
{
    public function __construct(
        protected AuditLogRepository $auditLogRepository,
        protected AddNotesHelper $addNotesHelper,
        protected VehicleCountHelper $vehicleCountHelper,
    ) {}

    #[Route("/roro/surveys/{surveyId}", name: "admin_roro_surveys_view")]
    public function view(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey,
        Request $request
    ): Response
    {
        $addNotesForm = $this->addNotesHelper->getForm(SurveyNote::class);
        if ($this->addNotesHelper->formSubmittedAndProcessed($request, $survey, $addNotesForm)) {
            return new RedirectResponse($this->addNotesHelper->addNotesTabAnchor($this->generateUrl('admin_roro_surveys_view', ['surveyId' => $survey->getId()])));
        }

        $this->vehicleCountHelper->setVehicleCountLabels($survey->getVehicleCounts());

        return $this->render('admin/roro/surveys/view.html.twig', [
            'addNoteForm' => $addNotesForm,
            'approvedBy' => in_array($survey->getState(), [
                SurveyStateInterface::STATE_INVITATION_SENT,
                SurveyStateInterface::STATE_NEW,
                SurveyStateInterface::STATE_IN_PROGRESS,
                SurveyStateInterface::STATE_REJECTED,
                SurveyStateInterface::STATE_CLOSED,
            ]) ? false : $this->auditLogRepository->getApprovedBy($survey),
            'auditLogs' => $this->auditLogRepository->getLogs($survey->getId(), Survey::class),
            'survey' => $survey,
        ]);
    }
}
