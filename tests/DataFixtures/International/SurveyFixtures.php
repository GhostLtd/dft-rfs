<?php

namespace App\Tests\DataFixtures\International;

use App\Entity\International\Company;
use App\Entity\International\Survey;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\RoRo\RouteAndPortFixtures;
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

        $company = (new Company())
            ->setBusinessName('Sprockets Ltd');

        $survey = (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2021-05-01'))
            ->setSurveyPeriodEnd(new \DateTime('2021-05-10'))
            ->setPasscodeUser($user)
            ->setState(SurveyStateInterface::STATE_NEW)
            ->setCompany($company)
            ->setInvitationEmails('sprockets@example.com')
            ->setReferenceNumber('101010-5059');

        $manager->persist($company);
        $manager->persist($survey);

        $this->addReference('survey:int:simple', $survey);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [UserFixtures::class, RouteAndPortFixtures::class];
    }
}
