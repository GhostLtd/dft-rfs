<?php

namespace App\Controller\Admin\Domestic;

use App\Utility\Domestic\DriverAvailabilityExportHelper;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/csrgt/driver-availability-export', name: 'admin_domestic_driveravailabilityexport_')]
class DriverAvailabilityExportController extends AbstractController
{
    public function __construct(protected DriverAvailabilityExportHelper $driverAvailabilityExportHelper)
    {
    }

    #[Route(path: '', name: 'index')]
    #[Template('admin/domestic/driver_availability_export/index.html.twig')]
    public function index(): array
    {
        return [
            'existingExportDates' => $this->driverAvailabilityExportHelper->getExistingDates(),
            'hasNewResponses' => $this->driverAvailabilityExportHelper->hasAnyResponsesReadyForNewExport(),
        ];
    }

    #[Route(path: '/all', name: 'all')]
    public function all(): Response
    {
        $response = new StreamedResponse(function() {
            $this->driverAvailabilityExportHelper->exportAll();
        });
        $this->addDownloadResponseHeaders($response, new \DateTime(), 'all');
        return $response;
    }

    #[Route(path: '/new', name: 'new')]
    public function new(): Response
    {
        $exportDate = new \DateTime();
        $response = new StreamedResponse(function() use ($exportDate) {
            $this->driverAvailabilityExportHelper->exportNew($exportDate);
        });
        $this->addDownloadResponseHeaders($response, $exportDate);
        return $response;
    }

    #[Route(path: '/existing/{date}', name: 'existing')]
    public function existing($date): Response
    {
        $exportDate = new \DateTime($date);
        $response = new StreamedResponse(function() use ($exportDate) {
            $this->driverAvailabilityExportHelper->exportExisting($exportDate);
        });
        $this->addDownloadResponseHeaders($response, $exportDate);
        return $response;
    }

    protected function addDownloadResponseHeaders(Response $response, \DateTime $date, $filenamePostfix = false): void
    {
        $filename = "csrgt-driver-availability-export"
            . ($filenamePostfix ? "-{$filenamePostfix}" : "")
            . ("-" . $date->format('Ymd-Hi'));
        $response->headers->set('Content-Type', "text/csv");
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
    }
}
