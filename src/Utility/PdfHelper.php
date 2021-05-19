<?php

namespace App\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use Dompdf\Dompdf;
use RuntimeException;
use Twig\Environment;

class PdfHelper
{
    const TEMPLATE_MAP = [
        DomesticSurvey::class => 'domestic_survey/view-whole-survey.html.twig',
        InternationalSurvey::class => 'international_survey/view-whole-survey.html.twig'
    ];

    protected Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function outputPDF(SurveyInterface $survey)
    {
        $dompdf = new Dompdf();
        $dompdf->getOptions()
            ->setIsHtml5ParserEnabled(true)
            ->setIsRemoteEnabled(true)
            ->setIsJavascriptEnabled(false)
        ->setDebugCss(true);

        $dompdf->loadHtml($this->getSurveyPdfHtml($survey));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
//
//        global $_dompdf_warnings;
//        dump($_dompdf_warnings);

        $dompdf->stream();
        exit;
    }

    public function getSurveyPdfHtml(SurveyInterface $survey): string
    {
        $template = self::TEMPLATE_MAP[get_class($survey)] ?? null;

        if (!$template) {
            throw new RuntimeException('Unsupported survey type');
        }

        return trim($this->twig->render($template, ['survey' => $survey]));
    }
}