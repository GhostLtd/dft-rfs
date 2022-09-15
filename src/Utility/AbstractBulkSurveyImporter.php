<?php


namespace App\Utility;


use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Vfs\FileSystem;

abstract class AbstractBulkSurveyImporter
{
    private $validator;
    protected NotificationInterceptionService $notificationInterception;

    abstract protected function parseLine($line);
    abstract public function createSurvey($surveyData, $surveyOptions = null);
    abstract protected function getAggregateSurveyOptionsAndValidate(FormInterface $form);

    public function __construct(ValidatorInterface $validator, NotificationInterceptionService $notificationInterception)
    {
        $this->validator = $validator;
        $this->notificationInterception = $notificationInterception;
    }

    public function getSurveys(FormInterface $form)
    {
        /** @var UploadedFile $file */
        $file = $form->get('file')->getData();

        $surveyOptions = $this->getAggregateSurveyOptionsAndValidate($form);
        if ($form->isValid()) {
            $data = $this->getDataFromFilename($file->getRealPath());
            return array_merge($this->processData($data['valid'], $surveyOptions), [
                'invalidLines' => $data['invalid'],
                'surveyOptions' => $surveyOptions,
                'filename' => $this->getOriginalFilename($file),
            ]);
        }
        return [];
    }

    public function processData($data, $surveyOptions = []) {
        $result = [
            'invalidData' => [],
            'invalidSurveys' => [],
            'surveys' => [],
        ];
        foreach ($data as $dataRow) {
            $survey = $this->createSurvey($dataRow, $surveyOptions);
            if ($survey) {
                $violations = $this->validate($survey);
                if ($violations->count() === 0) {
                    $result['surveys'][] = $survey;
                } else {
                    $result['invalidSurveys'][] = [
                        'survey' => $survey,
                        'violations' => $violations,
                    ];
                }
            } else {
                $result['invalidData'][] = $dataRow;
            }
        }
        return $result;
    }

    protected function getOriginalFilename(?UploadedFile $file) {
        if (!$file) {
            return null;
        }
        return pathinfo($file->getClientOriginalName(), PATHINFO_BASENAME);
    }

    public function getDataFromFilename($filename) {
        $surveyData = [
            'valid' => [],
            'invalid' => [],
        ];
        $handle = fopen($filename, "r");
        while(!feof($handle)){
            $line = trim(fgets($handle));
            if (empty($line)) continue;
            if ($lineData = $this->parseLine($line)) {
                $surveyData['valid'][] = $lineData;
            } else {
                $surveyData['invalid'][] = $line;
            }
        }
        return $surveyData;
    }

    protected function validate($survey)
    {
        return  $this->validator->validate($survey, null, ['import_survey', 'notify_api']);
    }
}