<?php

namespace App\Tests\DataFixtures;

use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PasscodeLoginFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $user = (new PasscodeUser())
            ->setUsername('testnullpassword');
        $this->setReference('user:frontend:null-password', $user);

        (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2021-05-01'))
            ->setSurveyPeriodEnd(new \DateTime('2021-05-10'))
            ->setIsNorthernIreland(true)
            ->setRegistrationMark('AB01 XYZ')
            ->setPasscodeUser($user)
            ->setState(Survey::STATE_NEW);

        $manager->persist($user);
        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [SurveyFixtures::class];
    }
}