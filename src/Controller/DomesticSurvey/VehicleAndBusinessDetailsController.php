<?php

namespace App\Controller\DomesticSurvey;

use App\Entity\Domestic\SurveyResponse;
use App\Workflow\DomesticSurvey\VehicleAndBusinessDetailsState;
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

class VehicleAndBusinessDetailsController extends AbstractSessionStateFormWizardController
{
    use SurveyHelperTrait;

    public function __construct(
        FormWizardManager                $formWizardManager,
        RequestStack                     $requestStack,
        PropertyMerger                   $propertyMerger,
        protected EntityManagerInterface $entityManager,
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    #[IsGranted(new Expression("is_granted('EDIT', user.getDomesticSurvey())"))]
    #[Route(path: '/domestic-survey/vehicle-and-business-details/{state}', name: 'app_domesticsurvey_vehicleandbusinessdetails_index')]
    #[Route(path: '/domestic-survey/vehicle-and-business-details', name: 'app_domesticsurvey_vehicleandbusinessdetails_start')]
    public function index(Request $request, ?string $state = null): Response
    {
        return $this->doWorkflow($request, $state);
    }

    protected function getState(): FormWizardStateInterface
    {
        $survey = $this->getSurvey();
        $databaseResponse = $this->entityManager->getRepository(SurveyResponse::class)->getBySurvey($survey);

        /** @var VehicleAndBusinessDetailsState|null $state */
        $state = $this->session->get($this->getSessionKey(), null);

        if (!$state) {
            $state = new VehicleAndBusinessDetailsState();
            $state->setSubject($databaseResponse);
        }

        $sessionResponse = $state->getSubject();
        $databaseResponse->mergeVehicleAndBusinessDetails($sessionResponse);
        $state->setSubject($databaseResponse);

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_domesticsurvey_vehicleandbusinessdetails_index', ['state' => (string) $place]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_domesticsurvey_contactdetails'));
    }
}
