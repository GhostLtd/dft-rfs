<?php

namespace App\Controller\InternationalPreEnquiry;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Company;
use App\Entity\International\PreEnquiry;
use App\Entity\International\PreEnquiryResponse;
use App\Form\InternationalPreEnquiry\SubmitPreEnquiryType;
use App\Repository\International\CompanyRepository;
use App\Repository\International\PreEnquiryRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalPreEnquiry\PreEnquiryState;
use DateTime;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class PreEnquiryController extends AbstractSessionStateWorkflowController
{
    public const WIZARD_ROUTE = 'app_international_preenquiry_wizard';
    public const SUMMARY_ROUTE = 'app_international_preenquiry_summary';

    /**
     * @Route("/pre-enquiry", name=PreEnquiryController::SUMMARY_ROUTE)
     */
    public function index(Request $request): Response
    {
        $preEnquiry = $this->getPreEnquiry();

        if ($preEnquiry->getSubmissionDate()) {
            return $this->render('international_pre_enquiry/thanks.html.twig');
        }

        if (!$preEnquiry->getResponse()) {
            return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => PreEnquiryState::STATE_INTRODUCTION]);
        }

        $form = $this->createForm(SubmitPreEnquiryType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $button = $form->get('submit');
            if ($button instanceof SubmitButton && $button->isClicked()) {
                $preEnquiry->setSubmissionDate(new DateTime());
                $this->entityManager->flush();

                return $this->redirectToRoute(self::SUMMARY_ROUTE);
            }
        }

        return $this->render('international_pre_enquiry/summary.html.twig', [
            'preEnquiry' => $preEnquiry,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/pre-enquiry/{state}", name=PreEnquiryController::WIZARD_ROUTE)
     */
    public function wizard(WorkflowInterface $preEnquiryStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($preEnquiryStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var PreEnquiryState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new PreEnquiryState());

        $subject = $this->getSurveyResponse($formWizard->getSubject());
        $subject = $this->entityManager->merge($subject);

        // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
        $formWizard->setSubject($subject);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => $state]);
    }

    protected function getSurveyResponse(?PreEnquiryResponse $existingResponse): PreEnquiryResponse
    {
        $preEnquiry = $this->getPreEnquiry();
        $response = $existingResponse ?? $preEnquiry->getResponse();

        if (!$response) {
            $response = new PreEnquiryResponse();
        }

        if ($response->getPreEnquiry() === null) {
            $response->setPreEnquiry($preEnquiry);
        }

        return $response;
    }

    protected function getPreEnquiry(): PreEnquiry
    {
        /** @var PreEnquiryRepository $preEnquiryRepo */
        $preEnquiryRepo = $this->entityManager->getRepository(PreEnquiry::class);

        /** @var CompanyRepository $companyRepo */
        $companyRepo = $this->entityManager->getRepository(Company::class);

        $preEnquiry = $preEnquiryRepo->findLatestSurveyForTesting();

        if (!$preEnquiry) {
            $company = $companyRepo->fetchOrCreateTestCompany();

            $notifiedDate = new DateTime();
            $dueDate = (clone $notifiedDate)->modify('+4 weeks');

            $preEnquiry = (new PreEnquiry())
                ->setCompany($company)
                ->setNotifiedDate($notifiedDate)
                ->setDueDate($dueDate);

            $this->entityManager->persist($preEnquiry);
            $this->entityManager->flush();
        }

        return $preEnquiry;
    }
}
