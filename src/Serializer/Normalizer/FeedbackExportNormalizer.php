<?php

namespace App\Serializer\Normalizer;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackExportNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    public const CONTEXT_KEY = 'feedback-export';
    use NormalizerAwareTrait;

    #[\Override]
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return ($context[self::CONTEXT_KEY] ?? false)
            && $data instanceof SurveyInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SurveyInterface::class => false,
        ];
    }

    /**
     * @param $object SurveyInterface|DomesticSurvey|InternationalSurvey|PreEnquiry
     * @throws ExceptionInterface
     */
    #[\Override]
    public function normalize($object, $format = null, array $context = []): array
    {
        $feedback = $this->normalizer->normalize($object->getFeedback(), 'csv');

        return array_merge($feedback, [
            'surveyType' => explode('\\', $object::class)[2],
            'surveyId' => $object->getId(),
            'refOrReg' => $object instanceof DomesticSurvey
                ? $object->getRegistrationMark()
                : $object->getReferenceNumber(),
        ]);
    }
}