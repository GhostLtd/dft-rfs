<?php


namespace App\Controller\Admin;


use App\Utility\Domestic\DriverAvailabilityExportHelper;
use App\Utility\SurveyFeedbackExportHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/survey-feedback/export", name="admin_surveyfeedback_export_")
 */
class SurveyFeedbackExportController extends AbstractController
{
    private SurveyFeedbackExportHelper $surveyFeedbackExportHelper;

    public function __construct(SurveyFeedbackExportHelper $surveyFeedbackExportHelper)
    {
        $this->surveyFeedbackExportHelper = $surveyFeedbackExportHelper;
    }

    /**
     * @Route("", name="index")
     * @Template("admin/survey_feedback/export/index.html.twig")
     */
    public function index(): array
    {
        return [
            'existingExportDates' => $this->surveyFeedbackExportHelper->getExistingDates(),
            'hasNewResponses' => $this->surveyFeedbackExportHelper->hasAnyFeedbackReadyForNewExport(),
        ];
    }

    /**
     * @Route("/all", name="all")
     */
    public function all(): Response
    {
        $response = new StreamedResponse(function() {
            $this->surveyFeedbackExportHelper->exportAll();
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
            $this->surveyFeedbackExportHelper->exportNew($exportDate);
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
            $this->surveyFeedbackExportHelper->exportExisting($exportDate);
        });
        $this->addDownloadResponseHeaders($response, $exportDate);
        return $response;
    }

    protected function addDownloadResponseHeaders(Response $response, \DateTime $date, $filenamePostfix = false)
    {
        $filename = "rfs-feedback-export"
            . ($filenamePostfix ? "-{$filenamePostfix}" : "")
            . ("-" . $date->format('Ymd-Hi'));
        $response->headers->set('Content-Type', "text/csv");
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
    }
}