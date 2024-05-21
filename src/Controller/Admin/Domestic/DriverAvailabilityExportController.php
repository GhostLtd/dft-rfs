<?php

namespace App\Controller\Admin\Domestic;

use App\Form\ConfirmationType;
use App\Repository\Domestic\SurveyRepository;
use App\Utility\Domestic\DriverAvailabilityDataExporter;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/csrgt/driver-availability-export', name: 'admin_domestic_driveravailabilityexport_')]
class DriverAvailabilityExportController extends AbstractController
{
    public function __construct(
        protected SurveyRepository $surveyRepository,
    ) {}

    #[Route(path: '', name: 'list')]
    #[Template('admin/domestic/driver_availability_export/list.html.twig')]
    public function index(): array
    {
        ['minDate' => $minDate, 'maxDate' => $date] = $this->surveyRepository->getSubmissionDateRange();

        $links = [];

        $minDate = new \DateTime($minDate);
        $date = new \DateTime($date);

        do {
            $links[] = [
                'month' => intval($date->format('m')),
                'year' => intval($date->format('Y')),
            ];

            $date->modify('-1 month');
        } while($date >= $minDate);

        return [
            'exports' => $links,
        ];
    }
    #[Route(path: '/{year}/{month}', name: 'year_and_month', requirements: ['year' => '\d{4}', 'month' => '[1-9]|10|11|12'])]
    #[Route(path: '/all', name: 'all')]
    public function exportYearAndMonth(DriverAvailabilityDataExporter $dataExporter, TranslatorInterface $translator, Request $request, ?string $year=null, ?string $month=null): Response
    {
        $form = $this->createForm(ConfirmationType::class, null, [
            'yes_label' => 'Export',
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $cancel = $form->get('no');
            $export = $form->get('yes');

            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return new RedirectResponse($this->generateUrl('admin_domestic_driveravailabilityexport_list'));
            } else if ($export instanceof SubmitButton && $export->isClicked()) {

                $filename = ($year !== null && $month !== null) ?
                    $dataExporter->generateExportDataForYearAndMonth($year, $month) :
                    $dataExporter->generateAllExportData();

                if ($filename) {
                    $now = new \DateTime();
                    return $this
                        ->file(new File($filename), "driver-availability_export_{$year}_{$month}_{$now->format('Ymd_Hi')}.sql")
                        ->deleteFileAfterSend();
                } else {
                    $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
                        $translator->trans('domestic.driver-availability-export.failed-notification.title', [], 'admin'),
                        $translator->trans('domestic.driver-availability-export.failed-notification.heading', [], 'admin'),
                        $translator->trans('domestic.driver-availability-export.failed-notification.content', [], 'admin')
                    ));
                    return new RedirectResponse($this->generateUrl('admin_domestic_driveravailabilityexport_list'));
                }
            }
        }

        return $this->render('admin/domestic/driver_availability_export/confirm.html.twig', [
            'month' => $month,
            'year' => $year,
            'form' => $form,
        ]);
    }
}
