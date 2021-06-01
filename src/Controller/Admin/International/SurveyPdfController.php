<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Utility\International\PdfHelper;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SurveyPdfController extends AbstractController
{
    /**
     * @Route("/irhs/surveys/{surveyId}.pdf")
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function pdf(Survey $survey, PdfHelper $pdfHelper, Request $request)
    {
        $pdf = $pdfHelper->getExistingSurveyPdfByTimestamp($survey, $request->query->get('timestamp'));

        if (!$pdf) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($pdf->getStorageObject()->signedUrl(new DateTime('+30 seconds')));
    }
}