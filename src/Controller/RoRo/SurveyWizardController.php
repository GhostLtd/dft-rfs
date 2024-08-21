<?php

namespace App\Controller\RoRo;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\RoRo\Survey;
use App\Utility\RoRo\VehicleCountHelper;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\RoRo\RoRoState;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route("/roro/survey/{surveyId}/edit/{state}", name: "app_roro_survey_")]
#[IsGranted("CAN_EDIT_RORO_SURVEY", "survey")]
class SurveyWizardController extends AbstractSessionStateWorkflowController
{
    protected Survey $databaseSurvey;

    #[Route(name: "edit")]
    public function wizard(
        WorkflowInterface $roroStateMachine,
        Request $request,
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey,
        VehicleCountHelper $vehicleCountHelper,
        ?string $state = null
    ): array | Response
    {
        $this->databaseSurvey = $survey;
        $vehicleCountHelper->setVehicleCountLabels($survey->getVehicleCounts());

        return $this->doWorkflow($roroStateMachine, $request, $state, [
            'operator' => $survey->getOperator(),
        ]);
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        /** @var RoRoState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), null);

        // If there's no session currently in progress, or if the survey in the session is a different survey to the
        // one mentioned in the URL, generate a new state.
        if (!$formWizard || $formWizard->getSubject()->getId() !== $this->databaseSurvey->getId()) {
            $formWizard = (new RoRoState())
                ->setSubject($this->databaseSurvey)->determineWhetherWizardPreviouslyCompleted();
        }


        $formData = $formWizard->getSubject();

        $this->databaseSurvey->merge($formData);
        $formWizard->setSubject($this->databaseSurvey);
        return $formWizard;
    }

    #[\Override]
    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute('app_roro_survey_edit', [
            'surveyId' => $this->databaseSurvey->getId(),
            'state' => $state
        ]);
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        return $this->redirectToRoute('app_roro_survey_view', [
            'surveyId' => $this->databaseSurvey->getId(),
        ]);
    }
}
