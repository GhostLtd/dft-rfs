<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Utility\Domestic\PdfHelper;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SurveyPdfController extends AbstractController
{
    /**
     * @Route("/csrgt/surveys/{surveyId}.pdf")
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function pdf(Survey $survey, PdfHelper $pdfHelper, Request $request): Response
    {
        $pdf = $pdfHelper->getExistingSurveyPdfByTimestamp($survey, $request->query->get('timestamp'));

        if (!$pdf) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($pdf->getStorageObject()->signedUrl(new DateTime('+30 seconds')));
    }

//    /**
//     * @Route("/csrgt/surveys/{surveyId}.pdf/generate")
//     * @Entity("survey", expr="repository.find(surveyId)")
//     *
//     * Generates a PDF and displays it immediately in the browser
//     */
//    public function generate(Survey $survey, PdfHelper $pdfHelper): Response
//    {
//        $dompdf = $pdfHelper->generatePDF($survey);
//        return new Response($dompdf->output(), 200, ['content-type' => 'application/pdf']);
//    }
}