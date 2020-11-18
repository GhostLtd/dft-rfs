<?php

namespace App\Controller\InternationalPreEnquiry;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\InternationalCompany;
use App\Entity\InternationalPreEnquiry;
use App\Entity\InternationalPreEnquiryResponse;
use App\Entity\SamplingGroup;
use App\Repository\InternationalPreEnquiryRepository;
use App\Repository\SamplingGroupRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalPreEnquiry\PreEnquiryState;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class InternationalPreEnquiryController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_internationalpreenquiry_index';


    /**
     * @Route("/pre-enquiry", name="app_internationalpreenquiry_start")
     */
    public function index(EntityManagerInterface $entityManager): Response
    {
        $preEnquiry = $this->getPreEnquiry($entityManager);

        return $this->render('international_pre_enquiry/summary.html.twig', [
            'preEnquiry' => $preEnquiry,
        ]);
    }

    /**
     * @Route("/pre-enquiry/{state}", name=InternationalPreEnquiryController::ROUTE_NAME)
     */
    public function wizard(WorkflowInterface $internationalPreSurveyStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($internationalPreSurveyStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var PreEnquiryState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new PreEnquiryState());

        if ($formWizard->getSubject() === null) {
            $formWizard->setSubject($this->getSurveyResponse());
        }

        // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
        $formWizard->setSubject($this->entityManager->merge($formWizard->getSubject()));
        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::ROUTE_NAME, ['state' => $state]);
    }

    protected function getSurveyResponse(): InternationalPreEnquiryResponse
    {
        /** @var InternationalPreEnquiryRepository $repo */
        $repo = $this->entityManager->getRepository(InternationalPreEnquiry::class);
        $preEnquiry = $repo->findLatestSurveyForTesting();
        $response = $preEnquiry->getResponse();

        if (!$response) {
            $response = (new InternationalPreEnquiryResponse())
                ->setPreEnquiry($preEnquiry);

            $this->entityManager->persist($response);
        }

        return $response;
    }

    protected function getPreEnquiry(EntityManagerInterface $entityManager): InternationalPreEnquiry
    {
        /** @var InternationalPreEnquiryRepository $preEnquiryRepo */
        $preEnquiryRepo = $entityManager->getRepository(InternationalPreEnquiry::class);

        $preEnquiry = $preEnquiryRepo->findOneBy([], ['id' => 'ASC']);

        if (!$preEnquiry) {
            /** @var SamplingGroupRepository $samplingGroup */
            $samplingGroupRepo = $entityManager->getRepository(SamplingGroup::class);

            /** @var SamplingGroup $samplingGroup */
            $samplingGroup = $samplingGroupRepo->findOneBy(['number' => 1, 'sizeGroup' => 1]);

            $company = (new InternationalCompany())
                ->setBusinessName('Test spockets inc')
                ->setSamplingGroup($samplingGroup);

            $dispatchDate = new DateTime();
            $dueDate = (clone $dispatchDate)->modify('+4 weeks');

            $preEnquiry = (new InternationalPreEnquiry())
                ->setCompany($company)
                ->setDispatchDate($dispatchDate)
                ->setDueDate($dueDate);

            $entityManager->persist($company);
            $entityManager->persist($preEnquiry);
            $entityManager->flush();
        }

        return $preEnquiry;
    }
}
