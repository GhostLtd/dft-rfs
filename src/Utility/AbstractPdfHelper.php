<?php

namespace App\Utility;

use App\Entity\SurveyInterface;
use Dompdf\Dompdf;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageObject;
use Twig\Environment;

abstract class AbstractPdfHelper
{
    abstract protected function getTemplate(): string;
    abstract protected function getName(SurveyInterface $survey): string;
    abstract protected function getPrefix(SurveyInterface $survey): string;
    abstract protected function getPdfObject(object $entity, StorageObject $obj): ?PdfObjectInterface;

    protected Environment $twig;
    protected Bucket $bucket;

    public function __construct(Environment $twig, Bucket $exportBucket)
    {
        $this->twig = $twig;
        $this->bucket = $exportBucket;
    }

    public function generateAndUploadPdf(SurveyInterface $survey): StorageObject
    {
        $pdf = $this->generatePDF($survey)->output();

        return $this->bucket->upload($pdf, [
            'resumable' => true,
            'name' => $this->getName($survey),
        ]);
    }

    public function getExistingSurveyPdfByTimestamp(SurveyInterface $survey, int $timestamp=null): ?PdfObjectInterface
    {
        $pdfs = $this->getExistingSurveyPDFs($survey);

        if (!empty($pdfs)) {
            if ($timestamp) {
                foreach ($pdfs as $pdf) {
                    if ($pdf->getTimestamp() === $timestamp) {
                        return $pdf;
                    }
                }
            } else {
                return reset($pdfs);
            }
        }

        return null;
    }

    public function getMostRecentSurveyPDF(SurveyInterface $survey): ?PdfObjectInterface
    {
        $pdfs = $this->getExistingSurveyPDFs($survey);
        if (empty($pdfs)) {
            return null;
        }
        return $pdfs[array_key_last($pdfs)];
    }

    /**
     * @return PdfObjectInterface[]
     */
    public function getExistingSurveyPDFs(SurveyInterface $survey): array
    {
        $iterator = $this->bucket->objects([
            'delimiter' => '/',
            'prefix' => $this->getPrefix($survey),
        ]);
        $pdfs = [];
        foreach(iterator_to_array($iterator) as $obj) {
            $pdfObject = $this->getPdfObject($survey, $obj);

            if (!$pdfObject) {
                // TODO: Log?
            } else {
                $pdfs[$pdfObject->getComparator()] = $pdfObject;
            }
        }

        krsort($pdfs);

        return $pdfs;
    }

    public function outputPDF(SurveyInterface $survey)
    {
        $this->generatePDF($survey)->stream();
        exit;
    }

    protected function generatePDF(SurveyInterface $survey): Dompdf
    {
        $dompdf = new Dompdf();
        $dompdf->getOptions()
            ->setIsHtml5ParserEnabled(true)
            ->setIsRemoteEnabled(true)
            ->setIsJavascriptEnabled(false)
            ->setIsPhpEnabled(true)
        ;

        $dompdf->loadHtml($this->getSurveyPdfHtml($survey));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        //        Options->setDebugCss(true); etc, and then...
        //        global $_dompdf_warnings;
        //        dump($_dompdf_warnings);
        //        exit;

        return $dompdf;
    }

    public function getSurveyPdfHtml(SurveyInterface $survey): string
    {
        return trim($this->twig->render($this->getTemplate(), ['survey' => $survey]));
    }

    private function processFooter(Dompdf $pdf): Dompdf
    {
        $canvas = $pdf->getCanvas();
        $pageCount = $canvas->get_page_count();
        $pageNumberMap = ['/{PAGE_COUNT}/', '/{PAGE_NUM}/'];
        $fontMetric = $pdf->getFontMetrics();
        $font = $fontMetric->getFont('sans-serif');
        $size = 10;
        $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
        $fontHeight = $fontMetric->getFontHeight($font, $size);
        $textWidth = $canvas->get_text_width(preg_replace($pageNumberMap, $pageCount, $text), $font, $size);
        $startY = $canvas->get_height() - $fontHeight - $this->mmToInchDots(10);
        $startX = $canvas->get_width() - $textWidth - $this->mmToInchDots(10);
        $canvas->page_text($startX, $startY, $text, $font, $size, [0, 0, 0]);

        return $pdf;
    }

    /**
     * DOMpdf units are based on 72dpi, regardless of the actual DPI!!
     */
    private function mmToInchDots($mm, $dpi = 72)
    {
        return ($mm / 25.4) * $dpi;
    }
}