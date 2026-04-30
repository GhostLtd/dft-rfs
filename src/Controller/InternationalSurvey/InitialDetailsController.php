<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use Doctrine\ORM\EntityManagerInterface;
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
class InitialDetailsController extends AbstractSessionStateFormWizardController
{
    use SurveyHelperTrait;

    protected SurveyResponse $surveyResponse;

    public function __construct(
        FormWizardManager                $formWizardManager,
        RequestStack                     $requestStack,
        PropertyMerger                   $propertyMerger,
        protected EntityManagerInterface $entityManager,
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    #[Route(path: '/international-survey/initial-details', name: 'app_internationalsurvey_initial_start')]
    #[Route(path: '/international-survey/initial-details/{state}', name: 'app_internationalsurvey_initial_wizard')]
    public function index(Request $request, ?string $state = null): Response
    {
        return $this->doWorkflow($request, $state);
    }

    protected function getState(): FormWizardStateInterface
    {
        $survey = $this->getSurvey();

        /** @var InitialDetailsState $state */
        $state = $this->session->get($this->getSessionKey(), new InitialDetailsState());

        $response = $state->getSubject() ?? $survey->getResponse() ?? new SurveyResponse();
        $databaseResponse = $survey->getResponse() ?? new SurveyResponse();
        $databaseResponse->mergeInitialDetails($response);
        $survey->setResponse($databaseResponse);
        $state->setSubject($databaseResponse);

        $this->surveyResponse = $databaseResponse;
        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_internationalsurvey_initial_wizard', ['state' => (string) $place]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return $this->surveyResponse->getId()
            ? new RedirectResponse($this->generateUrl(BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE))
            : null;
    }
}
