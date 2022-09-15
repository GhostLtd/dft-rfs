<?php

namespace App\Utility\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\SurveyInterface;
use App\Utility\AbstractPdfHelper;
use App\Utility\PdfObjectInterface;
use DateTime;
use Google\Cloud\Storage\StorageObject;

class PdfHelper extends AbstractPdfHelper
{
    const PREFIX = 'csrgt-pdf/';

    protected function getTemplate(): string
    {
        return 'domestic_survey/view-whole-survey.html.twig';
    }

    protected function getName(SurveyInterface $survey): string
    {
        $timestamp = (new DateTime())->format('U');
        return $this->getPrefix($survey)."_".$timestamp.".pdf";
    }

    protected function getPrefix(SurveyInterface $survey): string
    {
        assert($survey instanceof DomesticSurvey);
        return self::PREFIX.str_replace('-', '_', $survey->getReferenceNumber());
    }

    protected function getPdfObject(object $entity, StorageObject $obj): ?PdfObjectInterface
    {
        $regex = '#^'. self::PREFIX. "(?P<regMark>[A-Z0-9]+)_(?P<year>\d{4})(?P<reissue>_R)?_(?P<region>GB|NI)_(?P<timestamp>\d+)\.pdf$#";

        if (!$entity instanceof Survey) {
            return null;
        }

        if (!preg_match($regex, $obj->name(), $parts)) {
            return null;
        }

        return new PdfObject($entity, $obj, $parts['regMark'], $parts['region'], intval($parts['year']), !empty($parts['reissue']), intval($parts['timestamp']));
    }
}