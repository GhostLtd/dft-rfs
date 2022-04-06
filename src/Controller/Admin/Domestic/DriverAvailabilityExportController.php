<?php


namespace App\Controller\Admin\Domestic;


use App\Utility\Domestic\DriverAvailabilityExportHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/driver-availability-export", name="admin_domestic_driveravailabilityexport_")
 */
class DriverAvailabilityExportController extends AbstractController
{
    private DriverAvailabilityExportHelper $driverAvailabilityExportHelper;

    public function __construct(DriverAvailabilityExportHelper $driverAvailabilityExportHelper)
    {
        $this->driverAvailabilityExportHelper = $driverAvailabilityExportHelper;
    }

    /**
     * @Route("", name="index")
     * @Template("admin/domestic/driver_availability_export/index.html.twig")
     */
    public function index(): array
    {
        return [
            'existingExportDates' => $this->driverAvailabilityExportHelper->getExistingDates(),
            'hasNewResponses' => $this->driverAvailabilityExportHelper->hasAnyResponsesReadyForNewExport(),
        ];
    }

    /**
     * @Route("/all", name="all")
     */
    public function all(): Response
    {
        $response = new StreamedResponse(function() {
            $this->driverAvailabilityExportHelper->exportAll();
        });
        $this->addDownloadResponseHeaders($response, new \DateTime(), 'all');
        return $response;
    }

    /**
     * @Route("/new", name="new")
     */
    public function new(): Response
    {
        $exportDate = new \DateTime();
        $response = new StreamedResponse(function() use ($exportDate) {
            $this->driverAvailabilityExportHelper->exportNew($exportDate);
        });
        $this->addDownloadResponseHeaders($response, $exportDate);
        return $response;
    }

    /**
     * @Route("/existing/{date}", name="existing")
     */
    public function existing($date): Response
    {
        $exportDate = new \DateTime($date);
        $response = new StreamedResponse(function() use ($exportDate) {
            $this->driverAvailabilityExportHelper->exportExisting($exportDate);
        });
        $this->addDownloadResponseHeaders($response, $exportDate);
        return $response;
    }

    protected function addDownloadResponseHeaders(Response $response, \DateTime $date, $filenamePostfix = false)
    {
        $filename = "csrgt-driver-availability-export"
            . ($filenamePostfix ? "-{$filenamePostfix}" : "")
            . ("-" . $date->format('Ymd-Hi'));
        $response->headers->set('Content-Type', "text/csv");
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
    }
}