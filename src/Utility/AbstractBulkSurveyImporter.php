<?php

namespace App\Utility;

use App\Entity\SurveyInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractBulkSurveyImporter
{
    abstract protected function parseLine($line);

    abstract public function createSurvey($surveyData, $surveyOptions = null): ?SurveyInterface;

    abstract protected function getAggregateSurveyOptionsAndValidate(FormInterface $form);

    public function __construct(
        protected ValidatorInterface              $validator,
        protected NotificationInterceptionService $notificationInterception,
    ) {}

    public function getSurveys(FormInterface $form, bool $allowHistoricalDate = false): array
    {
        /** @var UploadedFile $file */
        $file = $form->get('file')->getData();

        $surveyOptions = $this->getAggregateSurveyOptionsAndValidate($form);
        if ($allowHistoricalDate) {
            $surveyOptions['allowHistoricalDate'] = true;
        }

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

    public function processData($data, $surveyOptions = []): array
    {
        $result = [
            'invalidData' => [],
            'invalidSurveys' => [],
            'surveys' => [],
        ];

        foreach ($data as $dataRow) {
            $survey = $this->createSurvey($dataRow, $surveyOptions);
            if ($survey) {
                $violations = $this->validate($survey, $surveyOptions);
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

    protected function getOriginalFilename(?UploadedFile $file): ?string
    {
        if (!$file) {
            return null;
        }
        return pathinfo($file->getClientOriginalName(), PATHINFO_BASENAME);
    }

    public function getDataFromFilename($filename): array
    {
        $surveyData = [
            'valid' => [],
            'invalid' => [],
        ];

        $handle = fopen($filename, "r");
        while (!feof($handle)) {
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

    protected function validate($survey, array $surveyOptions): ConstraintViolationListInterface
    {
        $groups = ['import_survey', 'notify_api'];

        if (($surveyOptions['allowHistoricalDate'] ?? false) === false) {
            $groups[] = 'import_survey_non_historical';
        }

        return $this->validator->validate($survey, null, $groups);
    }
}