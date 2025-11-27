<?php

namespace App\Tests\Unit\Serializer\Normalizer\PreEnquiry;

use App\Entity\Embeddable\LongAddress;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Serializer\Normalizer\PreEnquiry\PreEnquiryNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PreEnquiryNormalizerTest extends TestCase
{
    private PreEnquiryNormalizer $normalizer;
    private NormalizerInterface $dateTimeNormalizer;

    protected function setUp(): void
    {
        $this->dateTimeNormalizer = $this->createMock(NormalizerInterface::class);
        $this->normalizer = new PreEnquiryNormalizer();
        $this->normalizer->setNormalizer($this->dateTimeNormalizer);
    }

    public function testNormalizeWithHgvAndLgvSplit(): void
    {
        $preEnquiry = new PreEnquiry();
        $preEnquiry->setReferenceNumber('TEST123');
        $preEnquiry->setDispatchDate(new \DateTime('2025-01-15'));

        $address = new LongAddress();
        $address->setLine1('Test Company');
        $address->setLine2('123 Test Street');
        $address->setPostcode('TE1 1ST');

        $response = new PreEnquiryResponse();
        $response->setCompanyName('Test Company');
        $response->setIsCorrectCompanyName(true);
        $response->setIsCorrectAddress(true);
        $response->setContactAddress($address);
        $response->setContactName('Test Contact');
        $response->setContactTelephone('01234567890');
        $response->setContactEmail('test@example.com');
        $response->setTotalHgvCount(10);
        $response->setInternationalJourneyHgvCount(5);
        $response->setAnnualHgvJourneyEstimate(100);
        $response->setTotalLgvCount(20);
        $response->setInternationalJourneyLgvCount(8);
        $response->setAnnualLgvJourneyEstimate(200);

        $response->setPreEnquiry($preEnquiry);
        $preEnquiry->setResponse($response);

        $this->dateTimeNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($preEnquiry->getDispatchDate(), null, [DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])
            ->willReturn('2025-01-15');

        $result = $this->normalizer->normalize(
            $preEnquiry,
            null,
            [PreEnquiryNormalizer::CONTEXT_KEY => true]
        );

        $this->assertArrayHasKey('numberOfHgv', $result);
        $this->assertArrayHasKey('numberOfIntHgv', $result);
        $this->assertArrayHasKey('numberOfIntHgvJourneys', $result);
        $this->assertArrayHasKey('numberOfLgv', $result);
        $this->assertArrayHasKey('numberOfIntLgv', $result);
        $this->assertArrayHasKey('numberOfIntLgvJourneys', $result);

        $this->assertEquals(10, $result['numberOfHgv']);
        $this->assertEquals(5, $result['numberOfIntHgv']);
        $this->assertEquals(100, $result['numberOfIntHgvJourneys']);
        $this->assertEquals(20, $result['numberOfLgv']);
        $this->assertEquals(8, $result['numberOfIntLgv']);
        $this->assertEquals(200, $result['numberOfIntLgvJourneys']);
    }

    public function testNormalizeWithoutResponse(): void
    {
        $preEnquiry = new PreEnquiry();
        $preEnquiry->setReferenceNumber('TEST456');
        $preEnquiry->setDispatchDate(new \DateTime('2025-01-15'));

        $this->dateTimeNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($preEnquiry->getDispatchDate(), null, [DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])
            ->willReturn('2025-01-15');

        $result = $this->normalizer->normalize(
            $preEnquiry,
            null,
            [PreEnquiryNormalizer::CONTEXT_KEY => true]
        );

        $this->assertNull($result['numberOfHgv']);
        $this->assertNull($result['numberOfIntHgv']);
        $this->assertNull($result['numberOfIntHgvJourneys']);
        $this->assertNull($result['numberOfLgv']);
        $this->assertNull($result['numberOfIntLgv']);
        $this->assertNull($result['numberOfIntLgvJourneys']);
    }
}
