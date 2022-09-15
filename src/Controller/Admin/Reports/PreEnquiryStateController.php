<?php

namespace App\Controller\Admin\Reports;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Form\Admin\PreEnquiry\PreEnquiryReportFilterType;
use App\Form\Admin\ReportFilterType;
use App\Repository\PreEnquiry\PreEnquiryRepository;
use App\Utility\StateReportHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PreEnquiryStateController extends AbstractController
{
    protected PreEnquiryRepository $preEnquiryRepo;

    public function __construct(PreEnquiryRepository $preEnquiryRepository)
    {
        $this->preEnquiryRepo = $preEnquiryRepository;
    }

    /**
     * @Route("/state/pre-enquiry", name="state_pre_enquiry_type")
     */
    public function stateReportsDefaults(): Response
    {
        return $this->redirectToCurrentYear();
    }

    /**
     * @Route("/state/pre-enquiry/{year}", name="state_pre_enquiry_full", requirements={"year": "\d{4}"})
     */
    public function stateReports(Request $request, StateReportHelper $stateReportHelper, int $year): Response
    {
        [$start, $end] = self::getDateRangeForYear($year);

        $form = $this->getReportsFilterForm($request, $year);

        if ($form->isSubmitted()) {
            return $stateReportHelper->getFullRedirect($form->getData());
        }

        $excludeFromTotals = [
            DomesticSurvey::STATE_REISSUED,
        ];

        $stats = $this->preEnquiryRepo->getStateReportStats($start, $end);
        $type = ReportFilterType::TYPE_PRE_ENQUIRY;

        return $this->render('admin/report/state.html.twig', array_merge($form->getData(), [
            'stats' => $stats ?? null,
            'form' => $form->createView(),
            'type' => $type,
            'typeLabel' => 'Pre-Enquiry',
            'stateMappings' => $stateReportHelper->getStateReportMappingsByColumn($type),
            'excludeFromTotals' => $excludeFromTotals,
        ]));
    }

    protected function redirectToCurrentYear(): Response
    {
        $now = new \DateTime();

        return new RedirectResponse($this->generateUrl('admin_reports_state_pre_enquiry_full', [
            'year' => $now->format('Y'),
        ]));
    }

    private static function getDateRangeForYear(int $year): array
    {
        return [
            \DateTime::createFromFormat("Y-m-d H:i:s", "{$year}-01-01 00:00:00"),
            \DateTime::createFromFormat("Y-m-d H:i:s", ($year+1)."-01-01 00:00:00"),
        ];
    }

    protected function getReportsFilterForm(Request $request, int $year, array $excludeChoices=[]): FormInterface
    {
        $current = [
            'year' => $year,
            'type' => ReportFilterType::TYPE_PRE_ENQUIRY,
        ];

        [$minYear] = $this->preEnquiryRepo->getMinimumAndMaximumYear();

        $form = $this->createForm(PreEnquiryReportFilterType::class, $current, [
            'minYear' => $minYear,
            'excludeChoices' => $excludeChoices,
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
        }

        return $form;
    }
}
