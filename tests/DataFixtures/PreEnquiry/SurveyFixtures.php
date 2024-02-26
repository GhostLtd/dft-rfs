<?php

namespace App\Tests\DataFixtures\PreEnquiry;

use App\Entity\LongAddress;
use App\Entity\PasscodeUser;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SurveyFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var PasscodeUser $user */
        $user = $this->getReference('user:frontend', PasscodeUser::class);

        $address = (new LongAddress())
            ->setLine1('Wibble Sprockets Ltd')
            ->setLine2('30 Wibble Road')
            ->setLine3('Wibbleston')
            ->setLine4('Wibblesex')
            ->setLine5('')
            ->setLine6('')
            ->setPostcode('WA10 1AB');

        $preEnquiry = (new PreEnquiry())
            ->setPasscodeUser($user)
            ->setState(SurveyStateInterface::STATE_NEW)
            ->setReferenceNumber('12345')
            ->setInvitationAddress($address)
            ->setInvitationEmails('sprockets@example.com')
            ->setDispatchDate(new \DateTime('2021-06-01'))
            ->setCompanyName('Wibble Sprockets Ltd');

        $manager->persist($preEnquiry);

        $this->addReference('pre-enquiry:survey', $preEnquiry);
        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
