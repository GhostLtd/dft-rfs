<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Entity\International\Vehicle;
use App\Workflow\InternationalSurvey\VehicleState;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
class VehicleAddController extends AbstractSessionStateFormWizardController
{
    use SurveyHelperTrait;

    protected SurveyResponse $surveyResponse;

    public function __construct(
        FormWizardManager $formWizardManager,
        RequestStack      $requestStack,
        PropertyMerger    $propertyMerger,
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    #[Route(path: '/international-survey/add-vehicle', name: 'app_internationalsurvey_vehicle_add_start')]
    #[Route(path: '/international-survey/add-vehicle/{state}', name: 'app_internationalsurvey_vehicle_add_wizard')]
    public function index(Request $request, ?string $state = null): Response
    {
        $this->surveyResponse = $this->getSurveyResponse();
        return $this->doWorkflow($request, $state);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var VehicleState $state */
        $state = $this->session->get($this->getSessionKey(), new VehicleState());

        $vehicle = $state->getSubject() ?? new Vehicle();
        $vehicle->setSurveyResponse($this->surveyResponse);
        $state->setSubject($vehicle);

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_internationalsurvey_vehicle_add_wizard', ['state' => (string) $place]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl(IndexController::SUMMARY_ROUTE));
    }
}
