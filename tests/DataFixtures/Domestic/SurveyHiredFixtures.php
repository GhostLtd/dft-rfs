<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\PasscodeUser;
use App\Tests\DataFixtures\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SurveyHiredFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var PasscodeUser $user */
        $user = $this->getReference('user:frontend');

        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_ON_HIRE)
            ->setHireeEmail('test@example.com')
            ->setHireeEmail('Tester')
            ->setHireeTelephone('12345')
            ->setUnableToCompleteDate(new \DateTime('2021-04-02'));

        $survey = (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2021-04-01'))
            ->setSurveyPeriodEnd(new \DateTime('2021-04-10'))
            ->setIsNorthernIreland(true)
            ->setRegistrationMark('AB01 ABC')
            ->setPasscodeUser($user)
            ->setResponse($response)
            ->setState(Survey::STATE_CLOSED);

        $manager->persist($survey);
        $manager->persist($response);

        $this->setReference('survey:hired', $survey);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [UserFixtures::class];
    }
}