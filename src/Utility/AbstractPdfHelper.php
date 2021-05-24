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
            ->setIsJavascriptEnabled(false);

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
}