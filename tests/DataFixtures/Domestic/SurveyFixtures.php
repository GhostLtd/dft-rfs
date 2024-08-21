<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
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

        $survey = (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2021-05-01'))
            ->setSurveyPeriodEnd(new \DateTime('2021-05-10'))
            ->setIsNorthernIreland(true)
            ->setRegistrationMark('AB01 ABC')
            ->setPasscodeUser($user)
            ->setState(SurveyStateInterface::STATE_NEW);

        $manager->persist($user);

        $this->addReference('survey:simple', $survey);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
