<?php

namespace App\Controller\Admin\Reports;

use App\Form\Admin\ReportFilterType;
use App\Repository\AuditLog\AuditLogRepository;
use App\Repository\Domestic\SurveyRepository as DomesticSurveyRepository;
use App\Repository\International\SurveyRepository as InternationalSurveyRepository;
use App\Utility\Domestic\WeekNumberHelper as DomesticWeekNumberHelper;
use App\Utility\International\WeekNumberHelper as InternationalWeekNumberHelper;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractReportsController extends AbstractController
{
    protected DomesticSurveyRepository $domesticRepo;
    protected InternationalSurveyRepository $internationalRepo;
    protected AuditLogRepository $auditLogRepository;

    public function __construct(DomesticSurveyRepository $domesticRepo, InternationalSurveyRepository $internationalRepo, AuditLogRepository $auditLogRepository)
    {
        $this->domesticRepo = $domesticRepo;
        $this->internationalRepo = $internationalRepo;
        $this->auditLogRepository = $auditLogRepository;
    }

    protected function getTypeLabel(string $type): string
    {
        return array_flip(ReportFilterType::CHOICE_TYPES)[$type];
    }

    protected function redirectToCurrentQuarterAndYear(string $type, string $routeName): RedirectResponse
    {
        [$quarter, $year] = self::getQuarterAndYear($type, new \DateTime());

        return new RedirectResponse($this->generateUrl($routeName, [
            'type' => $type,
            'year' => $year,
            'quarter' => $quarter
        ]));
    }

    protected function getReportsFilterForm(Request $request, int $year, int $quarter, string $type, array $excludeChoices=[]): FormInterface
    {
        $current = [
            'year' => $year,
            'quarter' => $quarter,
            'type' => $type,
        ];

        [$intMinYear] = $this->internationalRepo->getMinimumAndMaximumYear();
        [$domMinYear] = $this->domesticRepo->getMinimumAndMaximumYear();
        $minYear = min($domMinYear, $intMinYear);

        $form = $this->createForm(ReportFilterType::class, $current, [
            'minYear' => $minYear,
            'excludeChoices' => $excludeChoices,
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
        }

        return $form;
    }

    protected static function getDateRangeForYearAndQuarter(string $type, int $year, int $quarter): array
    {
        return ($type === 'irhs') ?
            InternationalWeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter) :
            DomesticWeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter);
    }

    protected static function getQuarterAndYear(string $type, DateTimeInterface $dateTime): array
    {
        return ($type === 'irhs') ?
            InternationalWeekNumberHelper::getQuarterAndYear($dateTime) :
            DomesticWeekNumberHelper::getQuarterAndYear($dateTime);
    }
}
