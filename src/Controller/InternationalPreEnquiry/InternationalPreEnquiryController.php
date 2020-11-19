<?php

namespace App\Controller\InternationalPreEnquiry;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Company;
use App\Entity\International\PreEnquiry;
use App\Entity\International\PreEnquiryResponse;
use App\Entity\International\SamplingGroup;
use App\Form\InternationalPreEnquiry\SubmitPreEnquiryType;
use App\Repository\International\PreEnquiryRepository;
use App\Repository\International\SamplingGroupRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalPreEnquiry\PreEnquiryState;
use DateTime;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class InternationalPreEnquiryController extends AbstractSessionStateWorkflowController
{
    public const WIZARD_ROUTE = 'app_internationalpreenquiry_wizard';
    public const SUMMARY_ROUTE = 'app_internationalpreenquiry_summary';

    /**
     * @Route("/pre-enquiry", name=InternationalPreEnquiryController::SUMMARY_ROUTE)
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
     * @Route("/pre-enquiry/{state}", name=InternationalPreEnquiryController::WIZARD_ROUTE)
     */
    public function wizard(WorkflowInterface $internationalPreEnquiryStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($internationalPreEnquiryStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var PreEnquiryState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new PreEnquiryState());

        $formWizard->setSubject($this->getSurveyResponse($formWizard->getSubject()));

        // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
        $formWizard->setSubject($this->entityManager->merge($formWizard->getSubject()));

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

        $preEnquiry = $preEnquiryRepo->findLatestSurveyForTesting();

        if (!$preEnquiry) {
            /** @var SamplingGroupRepository $samplingGroup */
            $samplingGroupRepo = $this->entityManager->getRepository(SamplingGroup::class);

            /** @var SamplingGroup $samplingGroup */
            $samplingGroup = $samplingGroupRepo->findOneBy(['number' => 1, 'sizeGroup' => 1]);

            $company = (new Company())
                ->setBusinessName('Test spockets inc')
                ->setSamplingGroup($samplingGroup);

            $dispatchDate = new DateTime();
            $dueDate = (clone $dispatchDate)->modify('+4 weeks');

            $preEnquiry = (new PreEnquiry())
                ->setCompany($company)
                ->setDispatchDate($dispatchDate)
                ->setDueDate($dueDate);

            $this->entityManager->persist($company);
            $this->entityManager->persist($preEnquiry);
            $this->entityManager->flush();
        }

        return $preEnquiry;
    }
}
