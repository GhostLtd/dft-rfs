<?php

namespace App\Tests\DataFixtures;

use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SurveyFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var PasscodeUser $user */
        $user = $this->getReference('user:frontend');

        $survey = (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2021-05-01'))
            ->setSurveyPeriodEnd(new \DateTime('2021-05-10'))
            ->setIsNorthernIreland(true)
            ->setRegistrationMark('AB01 ABC')
            ->setInvitationEmail('test@example.com')
            ->setPasscodeUser($user)
            ->setState(Survey::STATE_NEW);

        $manager->persist($user);

        $this->setReference('survey:simple', $survey);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [UserFixtures::class];
    }
}