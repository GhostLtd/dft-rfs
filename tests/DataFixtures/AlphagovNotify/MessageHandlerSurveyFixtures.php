<?php


namespace App\Tests\DataFixtures\AlphagovNotify;


use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use App\Entity\HaulageSurveyInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MessageHandlerSurveyFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        /** @var HaulageSurveyInterface $survey */
        $survey = new Survey();
        $survey
            ->setSurveyPeriodStart(new \DateTime('2021-05-01'))
            ->setSurveyPeriodEnd(new \DateTime('2021-05-10'))
            ->setIsNorthernIreland(true)
            ->setRegistrationMark('AB01 ABC')
            ->setInvitationEmail('test@example.com')
            ->setPasscodeUser((new PasscodeUser())->setUsername('test')->setPlainPassword('test'))
            ->setState(Survey::STATE_INVITATION_PENDING);
        $manager->persist($survey);
        $this->addReference('survey:notify-message-handler', $survey);
        $manager->flush();
    }
}