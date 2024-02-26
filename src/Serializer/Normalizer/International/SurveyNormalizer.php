<?php

namespace App\Serializer\Normalizer\International;

use App\Entity\International\Survey;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SurveyNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    public const CONTEXT_KEY = 'for-export';

    use NormalizerAwareTrait;

    #[\Override]
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof Survey && ($context[self::CONTEXT_KEY] ?? false) === true;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Survey::class => false,
        ];
    }

    /**
     * @param Survey $object
     * @throws ExceptionInterface
     */
    #[\Override]
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
            'contactName' => $response?->getContactName(),
            'contactEmail' => $response?->getContactEmail(),
            'contactPhone' => $response?->getContactTelephone(),
            'numberOfIntJourneys' => $response?->getAnnualInternationalJourneyCount(),
            'activityStatus' => $response?->getActivityStatus(),
            'reasonForEmptySurvey' => $object->getReasonForEmptySurvey(),
            'reasonForEmptySurveyOther' => $object->getReasonForEmptySurvey() === Survey::REASON_FOR_EMPTY_SURVEY_OTHER ? $object->getReasonForEmptySurveyOther() : null,
            'businessNature' => $response?->getBusinessNature(),
            'businessSize' => $response?->getNumberOfEmployees(),
        ];
    }
}
