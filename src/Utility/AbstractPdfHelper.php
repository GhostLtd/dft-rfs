<?php

namespace App\Utility;

use App\Entity\SurveyInterface;
use Dompdf\Dompdf;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageObject;
use Psr\Log\LoggerInterface;
use Twig\Environment;

abstract class AbstractPdfHelper
{
    abstract protected function getTemplate(): string;
    abstract protected function getName(SurveyInterface $survey): string;
    abstract protected function getPrefix(SurveyInterface $survey): string;
    abstract protected function getPdfObject(object $entity, StorageObject $obj): ?PdfObjectInterface;

    protected Environment $twig;
    protected Bucket $bucket;
    protected LoggerInterface $logger;

    public function __construct(Environment $twig, Bucket $exportBucket, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->bucket = $exportBucket;
        $this->logger = $logger;
    }

    public function generateAndUploadPdfIfNotExists(SurveyInterface $survey): ?StorageObject
    {
        $existingSurvey = $this->getMostRecentSurveyPDF($survey);
        return $existingSurvey ? null : $this->generateAndUploadPdf($survey);
    }

    public function generateAndUploadPdf(SurveyInterface $survey): ?StorageObject
    {
        if (!$this->bucket->name()) {
            // Bucket isn't configured
            return null;
        }

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
        if (!$this->bucket->name()) {
            // Bucket isn't configured
            return [];
        }

        $pdfs = [];
        $iterator = $this->bucket->objects([
            'delimiter' => '/',
            'prefix' => $this->getPrefix($survey),
        ]);

        /** @var StorageObject $obj */
        foreach(iterator_to_array($iterator) as $obj) {
            $pdfObject = $this->getPdfObject($survey, $obj);

            if ($pdfObject) {
                $pdfs[$pdfObject->getComparator()] = $pdfObject;
            } else {
                $this->logger->alert("[PdfHelper] Unable to create PdfObject for resource: '{$obj->name()}'");
            }
        }

        krsort($pdfs);

        return $pdfs;
    }

    public function generatePDF(SurveyInterface $survey): Dompdf
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
}