<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Utility\Domestic\PdfHelper;
use DateTime;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class SurveyPdfController extends AbstractController
{
    #[Route(path: '/csrgt/surveys/{surveyId}.pdf')]
    public function pdf(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey    $survey,
        PdfHelper $pdfHelper,
        Request   $request
    ): Response
    {
        $pdf = $pdfHelper->getExistingSurveyPdfByTimestamp($survey, $request->query->get('timestamp'));

        if (!$pdf) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($pdf->getStorageObject()->signedUrl(new DateTime('+30 seconds')));
    }

    /**
     * Generates a PDF and displays it immediately in the browser
     */
    #[Route(path: '/csrgt/surveys/{surveyId}.pdf/generate')]
    public function generate(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey,
        PdfHelper $pdfHelper
    ): Response
    {
        $dompdf = $pdfHelper->generatePDF($survey);
        return new Response($dompdf->output(), 200, ['content-type' => 'application/pdf']);
    }
}
