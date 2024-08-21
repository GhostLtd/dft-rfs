<?php

namespace App\Serializer\Normalizer\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Serializer\Normalizer\Utils;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PreEnquiryNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    public const CONTEXT_KEY = 'for-export';

    use NormalizerAwareTrait;

    #[\Override]
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof PreEnquiry && ($context[self::CONTEXT_KEY] ?? false) === true;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PreEnquiry::class => false,
        ];
    }

    /**
     * @param PreEnquiry $object
     * @return array
     */
    #[\Override]
    public function normalize($object, $format = null, array $context = []): array
    {
        $response = $object->getResponse();

        // Although the code here can deal with the lack of a response (and outputs NULLs), this should never naturally
        // occur. The respondent should typically fill out the pre-enquiry stating they're not making international
        // journeys, but if an admin instead closes the survey unfilled, we could get a pre-enquiry without a response.

        return [
            'firmRef' => $object->getReferenceNumber(),
            'dispatchDate' => $this->normalizer->normalize($object->getDispatchDate(), $format, [DateTimeNormalizer::FORMAT_KEY => 'Y-m-d']),
            'companyNameDifferent' => $response ? Utils::formatBool(!$response->getIsCorrectCompanyName()) : null,
            'companyName' => $response?->getCompanyName(),
            'contactName' => $response?->getContactName(),
            'contactPhone' => $response?->getContactTelephone(),
            'contactEmail' => $response?->getContactEmail(),
            'addressDifferent' => $response ? Utils::formatBool(!$response->getIsCorrectAddress()) : null,
            // address line1 is always the same as company name.
            'addressLine1' => $response?->getContactAddress()->getLine2(),
            'addressLine2' => $response?->getContactAddress()->getLine3(),
            'addressLine3' => $response?->getContactAddress()->getLine4(),
            'addressLine4' => $response?->getContactAddress()->getLine5(),
            'addressLine5' => $response?->getContactAddress()->getLine6(),
            'addressPostcode' => $response?->getContactAddress()->getPostcode(),
            'numberOfHgv' => $response?->getTotalVehicleCount(),
            'numberOfIntHgv' => $response?->getInternationalJourneyVehicleCount(),
            'numberOfIntJourneys' => $response?->getAnnualJourneyEstimate(),
            'businessSize' => $response?->getNumberOfEmployees(),
        ];
    }
}
