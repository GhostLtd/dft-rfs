<?php

namespace App\Serializer\Normalizer;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class FeedbackExportNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    public const CONTEXT_KEY = 'feedback-export';
    use NormalizerAwareTrait;

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return ($context[self::CONTEXT_KEY] ?? false)
            && $data instanceof SurveyInterface;
    }

    /**
     * @param $object SurveyInterface|DomesticSurvey|InternationalSurvey|PreEnquiry
     * @throws ExceptionInterface
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $feedback = $this->normalizer->normalize($object->getFeedback(), 'csv');

        return array_merge($feedback, [
            'surveyType' => explode('\\', get_class($object))[2],
            'surveyId' => $object->getId(),
            'refOrReg' => $object instanceof DomesticSurvey
                ? $object->getRegistrationMark()
                : $object->getReferenceNumber(),
        ]);
    }
}