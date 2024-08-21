<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\Entity\Domestic\NotificationInterception;
use App\Entity\Domestic\Survey;
use App\Entity\LongAddress;
use App\Entity\SurveyStateInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DomesticLcniChangeSubscriberFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $intercept = (new NotificationInterception())
            ->setEmails('red@example.com')
            ->setAddressLine('123 Red Road');

        $manager->persist($intercept);

        $this->addReference('intercept', $intercept);

        foreach(['red@example.com', 'blue@example.com', null] as $emails) {
            foreach (['123 Red Road', '234 Blue Road'] as $addressLine) {
                foreach ([
                             SurveyStateInterface::STATE_NEW,
                             SurveyStateInterface::STATE_INVITATION_PENDING,
                             SurveyStateInterface::STATE_INVITATION_FAILED,
                             SurveyStateInterface::STATE_INVITATION_SENT,
                             SurveyStateInterface::STATE_IN_PROGRESS,
                             SurveyStateInterface::STATE_CLOSED,
                             SurveyStateInterface::STATE_REJECTED,
                             SurveyStateInterface::STATE_APPROVED,
                             Survey::STATE_REISSUED,
                         ] as $state) {
                    $address = (new LongAddress())
                        ->setLine1($addressLine);

                    // Put a code in the registration mark that shows:
                    // - email  : red / blue / null
                    // - address: red / blue
                    // - state  : active / closed
                    //
                    // (Allows us to more easily verify results in tests)
                    $regMark = strtoupper(
                        ($emails ? $emails[0] : 'N').
                        $addressLine[4].
                        (in_array($state, SurveyStateInterface::ACTIVE_STATES) ? 'A' : 'C')
                    );

                    $survey = (new Survey())
                        ->setState($state)
                        ->setInvitationEmails($emails)
                        ->setIsNorthernIreland(false)
                        ->setInvitationAddress($address)
                        ->setRegistrationMark($regMark)
                        ->setSurveyPeriodStart(new \DateTime())
                        ->setSurveyPeriodEnd(new \DateTime('+10 days'));

                    $manager->persist($survey);
                }
            }
        }

        $manager->flush();
    }
}