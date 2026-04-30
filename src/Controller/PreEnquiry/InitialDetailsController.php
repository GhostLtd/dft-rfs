<?php

namespace App\Controller\PreEnquiry;

use App\Entity\PasscodeUser;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Workflow\PreEnquiry\PreEnquiryState;
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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression("is_granted('EDIT', user.getPreEnquiry())"))]
class InitialDetailsController extends AbstractSessionStateFormWizardController
{
    public function __construct(
        FormWizardManager                $formWizardManager,
        RequestStack                     $requestStack,
        PropertyMerger                   $propertyMerger,
        protected EntityManagerInterface $entityManager,
    )
    {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    #[Route(path: '/pre-enquiry/{state}', name: PreEnquiryController::WIZARD_ROUTE, requirements: ['state' => '^(?!completed).+$'])]
    public function wizard(Request $request, string $state): Response
    {
        return $this->doWorkflow($request, $state);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $preEnquiry = $user instanceof PasscodeUser ? $user->getPreEnquiry() : null;
        if (!$preEnquiry instanceof PreEnquiry) {
            throw new AccessDeniedHttpException('No such pre-enquiry');
        }

        /** @var PreEnquiryState $state */
        $state = $this->session->get($this->getSessionKey(), new PreEnquiryState());

        $response = $state->getSubject() ?? $preEnquiry->getResponse() ?? new PreEnquiryResponse();
        $databaseResponse = $preEnquiry->getResponse() ?? new PreEnquiryResponse();
        $databaseResponse->mergeInitialDetails($response);
        $preEnquiry->setResponse($databaseResponse);
        $state->setSubject($databaseResponse);

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl(PreEnquiryController::WIZARD_ROUTE, ['state' => (string) $place]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        $response = $this->getState()->getSubject();
        return $response->getId()
            ? new RedirectResponse($this->generateUrl(PreEnquiryController::SUMMARY_ROUTE))
            : null;
    }
}
