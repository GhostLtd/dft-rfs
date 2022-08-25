<?php

namespace App\Serializer\Normalizer\International;

use App\Entity\International\Survey;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class SurveyNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    const CONTEXT_KEY = 'for-export';

    use NormalizerAwareTrait;

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof Survey && $context[self::CONTEXT_KEY] === true;
    }

    /**
     * @param Survey $object
     * @throws ExceptionInterface
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $response = $object->getResponse();
        return [
            'id' => $object->getId(),
            'refNumber' => $object->getReferenceNumber(),
            'status' => $object->getState(),
            'qa' => $this->normalizer->normalize($object->getQualityAssured() === true, $format, $context),
            'reminders' => $object->getSecondReminderSentDate() ? 2
                : ($object->getFirstReminderSentDate() ? 1 : 0),
            'contactName' => $response ? $response->getContactName() : null,
            'contactEmail' => $response ? $response->getContactEmail(): null,
            'contactPhone' => $response ? $response->getContactTelephone() : null,
            'numberOfIntJourneys' => $response ? $response->getAnnualInternationalJourneyCount() : null,
            'activityStatus' => $response ? $response->getActivityStatus() : null,
            'reasonForEmptySurvey' => $object->getReasonForEmptySurvey(),
            'reasonForEmptySurveyOther' => $object->getReasonForEmptySurvey() === Survey::REASON_FOR_EMPTY_SURVEY_OTHER ? $object->getReasonForEmptySurveyOther() : null,
            'businessNature' => $response ? $response->getBusinessNature() : null,
            'businessSize' => $response ? $response->getNumberOfEmployees() : null,
        ];
    }
}