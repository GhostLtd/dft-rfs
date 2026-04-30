<?php

namespace App\Controller\RoRo;

use App\Entity\RoRo\Survey;
use App\Utility\RoRo\VehicleCountHelper;
use App\Workflow\RoRo\RoRoState;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/roro/survey/{surveyId}/edit/{state}", name: "app_roro_survey_")]
#[IsGranted("CAN_EDIT_RORO_SURVEY", "survey")]
class SurveyWizardController extends AbstractSessionStateFormWizardController
{
    protected Survey $databaseSurvey;

    #[Route(name: "edit")]
    public function wizard(
        Request $request,
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey,
        VehicleCountHelper $vehicleCountHelper,
        ?string $state = null,
    ): Response {
        $this->databaseSurvey = $survey;
        $vehicleCountHelper->setVehicleCountLabels($survey->getVehicleCounts());

        return $this->doWorkflow($request, $state, [
            'operator' => $survey->getOperator(),
        ]);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var RoRoState|null $state */
        $state = $this->session->get($this->getSessionKey(), null);

        // If there's no session currently in progress, or if the survey in the session is a different survey to the
        // one mentioned in the URL, generate a new state.
        if (!$state || $state->getSubject()->getId() !== $this->databaseSurvey->getId()) {
            $state = (new RoRoState())
                ->setSubject($this->databaseSurvey)
                ->determineWhetherWizardPreviouslyCompleted();
        }

        $formData = $state->getSubject();
        $this->databaseSurvey->merge($formData);
        $state->setSubject($this->databaseSurvey);

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_roro_survey_edit', [
            'surveyId' => $this->databaseSurvey->getId(),
            'state' => (string) $place,
        ]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_roro_survey_view', [
            'surveyId' => $this->databaseSurvey->getId(),
        ]));
    }
}