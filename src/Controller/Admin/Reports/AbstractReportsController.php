<?php

namespace App\Controller\Admin\Reports;

use App\Form\Admin\ReportFilterType;
use App\Form\Admin\ReportYearlyFilterType;
use App\Repository\AuditLog\AuditLogRepository;
use App\Utility\Quarter\QuarterHelperProvider;
use App\Utility\Reports\DateRangeHelper;
use App\Utility\Reports\DomesticReportsHelper;
use App\Utility\Reports\InternationalReportsHelper;
use App\Utility\Reports\PreEnquiryReportsHelper;
use App\Utility\Reports\RoRoReportsHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractReportsController extends AbstractController
{
    public function __construct(
        protected DomesticReportsHelper      $domesticReportsHelper,
        protected InternationalReportsHelper $internationalReportsHelper,
        protected PreEnquiryReportsHelper    $preEnquiryReportsHelper,
        protected RoRoReportsHelper          $roRoReportsHelper,
        protected AuditLogRepository         $auditLogRepository,
        protected DateRangeHelper            $dateRangeHelper,
        protected QuarterHelperProvider      $quarterHelperProvider,
    ) {}

    protected function getTypeLabel(string $type): string
    {
        return array_flip(ReportFilterType::CHOICE_TYPES)[$type];
    }

    protected function getReportsFilterForm(Request $request, int $year, int $quarter, string $type, array $excludeChoices = []): FormInterface
    {
        $current = [
            'year' => $year,
            'quarter' => $quarter,
            'type' => $type,
        ];

        [$minYear] = $this->dateRangeHelper->getMinAndMaxYearsForAllSurveys();

        $form = $this->createForm(ReportFilterType::class, $current, [
            'minYear' => $minYear,
            'excludeChoices' => $excludeChoices,
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
        }

        return $form;
    }

    protected function getReportsYearlyFilterForm(Request $request, int $year, string $type, array $excludeChoices = []): FormInterface
    {
        $current = [
            'year' => $year,
            'type' => $type,
        ];

        [$minYear] = $this->dateRangeHelper->getMinAndMaxYearsForAllSurveys();

        $form = $this->createForm(ReportYearlyFilterType::class, $current, [
            'minYear' => $minYear,
            'excludeChoices' => $excludeChoices,
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
        }

        return $form;
    }

    protected function getDateRangeForYearAndQuarter(string $type, int $year, int $quarter): array
    {
        return $this->quarterHelperProvider
            ->getQuarterHelperByReportClass($type)
            ->getDateRangeForYearAndQuarter($year, $quarter);
    }

    protected function getDateRangeForYear(int $year): array
    {
        return [
            \DateTime::createFromFormat("Y-m-d H:i:s", "{$year}-01-01 00:00:00"),
            \DateTime::createFromFormat("Y-m-d H:i:s", ($year + 1) . "-01-01 00:00:00"),
        ];
    }

    protected function handleRedirect(string $type): RedirectResponse
    {
        [$quarter, $year] = $this->quarterHelperProvider
            ->getQuarterHelperByReportClass($type)
            ->getQuarterAndYear(new \DateTime());

        return $this->getRedirect(['type' => $type, 'year' => $year, 'quarter' => $quarter]);
    }

    abstract protected function getRedirect(array $data): RedirectResponse;
}
