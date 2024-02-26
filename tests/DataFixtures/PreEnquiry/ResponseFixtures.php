<?php

namespace App\Tests\DataFixtures\PreEnquiry;

use App\Entity\LongAddress;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Entity\SurveyStateInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ResponseFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var PreEnquiry $preEnquiry */
        $preEnquiry = $this->getReference('pre-enquiry:survey', PreEnquiry::class);

        $preEnquiry->setState(SurveyStateInterface::STATE_IN_PROGRESS);

        $address = (new LongAddress())
            ->setLine1('Wabble Sockets Ltd')
            ->setLine2('40 Dibble Road')
            ->setLine3('Dibbleston')
            ->setLine4('Dibblesex')
            ->setLine5('')
            ->setLine6('')
            ->setPostcode('DA10 1AB');

        $response = (new PreEnquiryResponse())
            ->setIsCorrectCompanyName(false)
            ->setCompanyName('Wabble Sockets Ltd')
            ->setPreEnquiry($preEnquiry)
            ->setAnnualJourneyEstimate(51)
            ->setInternationalJourneyVehicleCount(20)
            ->setTotalVehicleCount(70)
            ->setContactName('Bob Bobbington')
            ->setContactEmail('bob@example.com')
            ->setContactTelephone('8118181')
            ->setIsCorrectAddress(false)
            ->setContactAddress($address)
            ->setNumberOfEmployees('10-49');

        $manager->persist($response);

        $this->addReference('pre-enquiry:response', $response);
        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [SurveyFixtures::class];
    }
}
