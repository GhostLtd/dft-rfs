<?php

namespace App\Controller\DomesticSurvey;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Workflow\DomesticSurvey\InitialDetailsState;
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

class InitialDetailsController extends AbstractSessionStateFormWizardController
{
    public const ROUTE_NAME = 'app_domesticsurvey_initialdetails_index';

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
    #[Route(path: '/domestic-survey/initial-details/{state}', name: self::ROUTE_NAME)]
    #[Route(path: '/domestic-survey/initial-details', name: 'app_domesticsurvey_initialdetails_start')]
    public function index(Request $request, ?string $state = null): Response
    {
        return $this->doWorkflow($request, $state);
    }

    protected function getState(): FormWizardStateInterface
    {
        $survey = $this->getSurvey();
        $databaseSurvey = $this->entityManager->getRepository(Survey::class)
            ->findOneByIdWithResponseAndVehicle($survey->getId());

        /** @var InitialDetailsState|null $state */
        $state = $this->session->get($this->getSessionKey(), null);

        if (!$state) {
            $state = new InitialDetailsState();
            $databaseResponse = $databaseSurvey->getResponse();
            if (!$databaseResponse) {
                // Fresh survey: create response + vehicle
                $databaseResponse = new SurveyResponse();
                (new Vehicle())
                    ->setRegistrationMark($databaseSurvey->getRegistrationMark())
                    ->setResponse($databaseResponse);
                $databaseResponse->setSurvey($databaseSurvey);
            }
            $state->setSubject($databaseResponse);
        }

        if ($state->getSubject()->getId()) {
            $databaseResponse = $databaseSurvey->getResponse();
            $databaseResponse->mergeInitialDetails($state->getSubject());
            $state->setSubject($databaseResponse);
        } else {
            $databaseSurvey->setResponse($state->getSubject());
        }

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl(self::ROUTE_NAME, ['state' => (string) $place]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        $response = $this->getState()->getSubject();
        return $response->getId()
            ? new RedirectResponse($this->generateUrl('app_domesticsurvey_contactdetails'))
            : null;
    }
}
