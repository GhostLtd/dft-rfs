<?php

namespace App\Utility\International;

use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use App\Utility\AbstractPdfHelper;
use App\Utility\PdfObjectInterface;
use DateTime;
use Google\Cloud\Storage\StorageObject;

class PdfHelper extends AbstractPdfHelper
{
    const PREFIX = 'irhs-pdf/';

    protected function getTemplate(): string
    {
        return 'international_survey/view-whole-survey.html.twig';
    }

    protected function getName(SurveyInterface $survey): string
    {
        $timestamp = (new DateTime())->format('U');
        return $this->getPrefix($survey)."_".$timestamp.".pdf";
    }

    protected function getPrefix(SurveyInterface $survey): string
    {
        assert($survey instanceof InternationalSurvey);
        $firmReference = $survey->getReferenceNumber();
        $week = $survey->getWeekNumber();

        return self::PREFIX."{$firmReference}_{$week}";
    }

    protected function getPdfObject(object $entity, StorageObject $obj): ?PdfObjectInterface
    {
        $regex = '#^'. preg_quote(self::PREFIX) . "(?P<firmReference>[A-Za-z0-9-]+)_(?P<week>\d+)_(?P<timestamp>\d+)\.pdf$#";

        if (!$entity instanceof InternationalSurvey) {
            return null;
        }

        if (!preg_match($regex, $obj->name(), $parts)) {
            return null;
        }

        return new PdfObject($entity, $obj, $parts['firmReference'], intval($parts['week']), intval($parts['timestamp']));
    }
}