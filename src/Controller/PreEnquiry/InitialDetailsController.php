<?php

namespace App\Controller\PreEnquiry;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\PasscodeUser;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\PreEnquiry\PreEnquiryState;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getPreEnquiry())"))]
class InitialDetailsController extends AbstractSessionStateWorkflowController
{
    protected ?PreEnquiryResponse $preEnquiryResponse = null;

    #[Route(path: '/pre-enquiry/{state}', name: PreEnquiryController::WIZARD_ROUTE, requirements: ['state' => '^(?!completed).+$'])]
    public function wizard(WorkflowInterface $preEnquiryInitialDetailsStateMachine, Request $request, string $state): Response
    {
        return $this->doWorkflow($preEnquiryInitialDetailsStateMachine, $request, $state);
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        $preEnquiry = $this->getPreEnquiry($this->getUser());

        /** @var PreEnquiryState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new PreEnquiryState());

        $response = $formWizard->getSubject() ?? $preEnquiry->getResponse() ?? new PreEnquiryResponse();

        $databaseResponse = $preEnquiry->getResponse() ?? new PreEnquiryResponse();
        $databaseResponse->mergeInitialDetails($response);

        $preEnquiry->setResponse($databaseResponse);
        $formWizard->setSubject($databaseResponse);

        $this->preEnquiryResponse = $databaseResponse;
        return $formWizard;
    }

    #[\Override]
    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(PreEnquiryController::WIZARD_ROUTE, ['state' => $state]);
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        return $this->preEnquiryResponse->getId() ? $this->redirectToRoute(PreEnquiryController::SUMMARY_ROUTE) : null;
    }

    protected function getPreEnquiry(UserInterface $user): PreEnquiry
    {
        $preEnquiry = ($user instanceof PasscodeUser) ? $user->getPreEnquiry() : null;

        if (!$preEnquiry || !$preEnquiry instanceof PreEnquiry) {
            throw new AccessDeniedHttpException('No such pre-enquiry');
        }

        return $preEnquiry;
    }
}
