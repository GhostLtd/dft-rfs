<?php

namespace App\Tests\DataFixtures\AlphagovNotify;

use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MessageHandlerSurveyFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $survey = new Survey();
        $survey
            ->setSurveyPeriodStart(new \DateTime('2021-05-01'))
            ->setSurveyPeriodEnd(new \DateTime('2021-05-10'))
            ->setIsNorthernIreland(true)
            ->setRegistrationMark('AB01 ABC')
            ->setPasscodeUser((new PasscodeUser())->setUsername('test')->setPlainPassword('test'))
            ->setState(SurveyStateInterface::STATE_INVITATION_PENDING);
        $manager->persist($survey);
        $this->addReference('survey:notify-message-handler', $survey);
        $manager->flush();
    }
}