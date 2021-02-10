<?php


namespace App\Utility;


use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class AbstractBulkSurveyImporter
{
    abstract public function getDataAndOptions(FormInterface $form);
    abstract protected function parseLine($line);
    abstract public function createSurvey($surveyData, $surveyOptions = null);

    protected function getOriginalFilename(?UploadedFile $file) {
        if (!$file) {
            return null;
        }
        return pathinfo($file->getClientOriginalName(), PATHINFO_BASENAME);
    }

    public function getDataFromUploadedFile(?UploadedFile $file)
    {
        if (!$file) {
            return null;
        }
        return $this->getDataFromFilename($file->getRealPath());
    }

    public function getDataFromFilename($filename) {
        $surveyData = [];
        $handle = fopen($filename, "r");
        while(!feof($handle)){
            $line = trim(fgets($handle));
            if (empty($line)) continue;
            if ($lineData = $this->parseLine($line)) $surveyData[] = $lineData;
        }
        return $surveyData;
    }

}