<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\Entity\International\Company;
use App\Entity\International\NotificationInterception;
use App\Entity\International\NotificationInterceptionCompanyName;
use App\Entity\International\Survey;
use App\Entity\SurveyStateInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class InternationalLcniChangeSubscriberFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $name = (new NotificationInterceptionCompanyName())
            ->setName('Red Ranger');

        $intercept = (new NotificationInterception())
            ->setEmails('red@example.com')
            ->setPrimaryName('Red Rover')
            ->addAdditionalName($name);

        $manager->persist($intercept);
        $manager->persist($name);

        $this->addReference('intercept:1', $intercept);

        $name = (new NotificationInterceptionCompanyName())
            ->setName('Purple Rover');

        $intercept = (new NotificationInterception())
            ->setEmails('blue@example.com')
            ->setPrimaryName('Green Rover')
            ->addAdditionalName($name);

        $manager->persist($intercept);
        $manager->persist($name);

        $this->addReference('intercept:2', $intercept);

        foreach(['red@example.com', 'blue@example.com', null] as $emails) {
            foreach (['Red Rover', 'Blue Rover', 'Orange Rover', 'Purple Rover'] as $companyName) {
                foreach ([
                             SurveyStateInterface::STATE_NEW,
                             SurveyStateInterface::STATE_INVITATION_PENDING,
                             SurveyStateInterface::STATE_INVITATION_FAILED,
                             SurveyStateInterface::STATE_INVITATION_SENT,
                             SurveyStateInterface::STATE_IN_PROGRESS,
                             SurveyStateInterface::STATE_CLOSED,
                             SurveyStateInterface::STATE_REJECTED,
                             SurveyStateInterface::STATE_APPROVED,
                         ] as $state) {

                    // Put a code in the reference number that shows:
                    // - email  : red / blue / null
                    // - company: red / blue
                    // - state  : active / closed
                    //
                    // (Allows us to more easily verify results in tests)
                    $refNumber = strtoupper(
                        ($emails ? $emails[0] : 'N').
                        $companyName[0].
                        (in_array($state, SurveyStateInterface::ACTIVE_STATES) ? 'A' : 'C')
                    );

                    $company = (new Company())
                        ->setBusinessName($companyName);

                    $survey = (new Survey())
                        ->setState($state)
                        ->setInvitationEmails($emails)
                        ->setCompany($company)
                        ->setReferenceNumber($refNumber)
                        ->setSurveyPeriodStart(new \DateTime())
                        ->setSurveyPeriodEnd(new \DateTime('+10 days'));

                    $manager->persist($company);
                    $manager->persist($survey);
                }
            }
        }

        $manager->flush();
    }
}
