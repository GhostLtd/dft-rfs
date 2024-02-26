<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Utility\International\PdfHelper;
use DateTime;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class SurveyPdfController extends AbstractController
{
    #[Route(path: '/irhs/surveys/{surveyId}.pdf')]
    public function pdf(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey    $survey,
        PdfHelper $pdfHelper,
        Request   $request
    ): RedirectResponse
    {
        $pdf = $pdfHelper->getExistingSurveyPdfByTimestamp($survey, $request->query->get('timestamp'));

        if (!$pdf) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($pdf->getStorageObject()->signedUrl(new DateTime('+30 seconds')));
    }
}
