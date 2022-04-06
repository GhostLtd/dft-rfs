<?php


namespace App\Serializer\Normalizer\PreEnquiry;


use App\Entity\PreEnquiry\PreEnquiry;
use App\Serializer\Normalizer\Utils;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class PreEnquiryNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof PreEnquiry;
    }

    /**
     * @param PreEnquiry $object
     * @return array
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $response = $object->getResponse();
        return [
            'firmRef' => $object->getReferenceNumber(),
            'dispatchDate' => $this->normalizer->normalize($object->getDispatchDate(), $format, [DateTimeNormalizer::FORMAT_KEY => 'Y-m-d']),
            'companyNameDifferent' => Utils::formatBool(!$response->getIsCorrectCompanyName()),
            'companyName' => $response->getCompanyName(),
            'contactName' => $response->getContactName(),
            'contactPhone' => $response->getContactTelephone(),
            'contactEmail' => $response->getContactEmail(),
            'addressDifferent' => Utils::formatBool(!$response->getIsCorrectAddress()),
            // address line1 is always the same as company name.
            'addressLine1' => $response->getContactAddress()->getLine2(),
            'addressLine2' => $response->getContactAddress()->getLine3(),
            'addressLine3' => $response->getContactAddress()->getLine4(),
            'addressLine4' => $response->getContactAddress()->getLine5(),
            'addressLine5' => $response->getContactAddress()->getLine6(),
            'addressPostcode' => $response->getContactAddress()->getPostcode(),
            'numberOfHgv' => $response->getTotalVehicleCount(),
            'numberOfIntHgv' => $response->getInternationalJourneyVehicleCount(),
            'numberOfIntJourneys' => $response->getAnnualJourneyEstimate(),
            'businessSize' => $response->getNumberOfEmployees(),
        ];
    }
}