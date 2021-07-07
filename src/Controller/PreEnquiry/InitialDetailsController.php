<?php

namespace App\Controller\PreEnquiry;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\PasscodeUser;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\PreEnquiry\PreEnquiryState;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @Security("is_granted('EDIT', user.getPreEnquiry())")
 */
class InitialDetailsController extends AbstractSessionStateWorkflowController
{
    protected ?PreEnquiryResponse $preEnquiryResponse;

    /**
     * @Route("/pre-enquiry/{state}", name=PreEnquiryController::WIZARD_ROUTE, requirements={"state": "^(?!completed).+$"})
     */
    public function wizard(WorkflowInterface $preEnquiryInitialDetailsStateMachine, Request $request, $state): Response
    {
        return $this->doWorkflow($preEnquiryInitialDetailsStateMachine, $request, $state);
    }

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

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(PreEnquiryController::WIZARD_ROUTE, ['state' => $state]);
    }

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