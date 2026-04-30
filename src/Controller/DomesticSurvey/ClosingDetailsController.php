<?php

namespace App\Controller\DomesticSurvey;

use App\Attribute\Redirect;
use App\Entity\Domestic\SurveyResponse;
use App\Workflow\DomesticSurvey\ClosingDetailsState;
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

class ClosingDetailsController extends AbstractSessionStateFormWizardController
{
    public const string WIZARD_ROUTE = 'app_domesticsurvey_closingdetails_wizard';
    public const string START_ROUTE = 'app_domesticsurvey_closingdetails_start';

    use SurveyHelperTrait;

    public function __construct(
        FormWizardManager                $formWizardManager,
        RequestStack                     $requestStack,
        PropertyMerger                   $propertyMerger,
        protected EntityManagerInterface $entityManager,
    )
    {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    #[Route(path: '/domestic-survey/closing-details', name: self::START_ROUTE)]
    #[Route(path: '/domestic-survey/closing-details/{state}', name: self::WIZARD_ROUTE)]
    #[Redirect("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())", route: "app_domesticsurvey_completed")]
    #[IsGranted(new Expression("is_granted('CLOSING_DETAILS', user.getDomesticSurvey())"))]
    public function index(Request $request, ?string $state = null): Response
    {
        return $this->doWorkflow($request, $state);
    }

    protected function getState(): FormWizardStateInterface
    {
        $survey = $this->getSurvey();
        $databaseResponse = $this->entityManager->getRepository(SurveyResponse::class)
            ->getBySurvey($survey);

        /** @var ClosingDetailsState $state */
        $state = $this->session->get($this->getSessionKey(), new ClosingDetailsState());

        $sessionResponse = $state->getSubject();
        if ($sessionResponse) {
            $driverAvailability = $sessionResponse->getSurvey()->getDriverAvailability();
            $driverAvailability = $databaseResponse->getSurvey()->mergeDriverAvailability($driverAvailability);

            $vehicle = $sessionResponse->getVehicle();
            $databaseResponse->mergeVehicleFuel($vehicle);

            $databaseResponse->mergeReasonForEmptySurvey($sessionResponse);

            if (!$driverAvailability->getId()) {
                $this->entityManager->persist($driverAvailability);
            }
        }

        $state->setSubject($databaseResponse);
        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl(self::WIZARD_ROUTE, ['state' => (string)$place]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl(IndexController::SUMMARY_ROUTE));
    }
}
