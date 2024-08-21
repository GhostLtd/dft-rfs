<?php

namespace App\Tests\Utility\Domestic;

use App\Entity\Domestic\NotificationInterception as DomesticIntercept;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\LongAddress;
use App\Entity\SurveyStateInterface;
use App\Tests\Utility\AbstractNotificationInterceptionServiceTest;

class NotificationInterceptionServiceTest extends AbstractNotificationInterceptionServiceTest
{
    public function dataDomesticIntercept(): array
    {
        return [
            [
                true, // Matches and no existing email
                null,
                '123 Test Road',
                'mango@example.com,banana@example.com'
            ],
            [
                false, // No match
                null,
                '456 Test Road',
                'mango@example.com,banana@example.com'
            ],
            [
                false, // Match but existing email
                'carrot@example.com',
                '123 Test Road',
                'mango@example.com,banana@example.com'
            ],
            [
                false, // No match
                'carrot@example.com',
                '456 Test Road',
                'mango@example.com,banana@example.com'
            ],
        ];
    }

    /**
     * @dataProvider dataDomesticIntercept
     */
    public function testDomesticIntercept(bool $expectedToChange, ?string $surveyEmails, string $interceptAddressLine, string $interceptEmails): void
    {
        $survey = $this->addDomesticSurvey('123 Test Road', $surveyEmails);
        $this->addDomesticIntercept($interceptAddressLine, $interceptEmails);
        $this->entityManager->flush();

        $this->interceptService->checkAndInterceptSurvey($survey);

        $expectedEmails = $expectedToChange ? $interceptEmails : $surveyEmails;
        $this->assertEquals($expectedEmails, $survey->getInvitationEmails());
    }

    protected function addDomesticIntercept(string $addressLine, string $emails): void {
        $intercept = (new DomesticIntercept())
            ->setAddressLine($addressLine)
            ->setEmails($emails);

        $this->entityManager->persist($intercept);
    }

    protected function addDomesticSurvey(string $addressLine1, ?string $invitationEmails): DomesticSurvey {
        $address = (new LongAddress())
            ->setLine1($addressLine1);

        $survey = (new DomesticSurvey())
            ->setInvitationAddress($address)
            ->setInvitationEmails($invitationEmails)
            ->setIsNorthernIreland(false)
            ->setRegistrationMark('AB01ABC')
            ->setState(SurveyStateInterface::STATE_IN_PROGRESS);

        $this->entityManager->persist($survey);

        return $survey;
    }
}