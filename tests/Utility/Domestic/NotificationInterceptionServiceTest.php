<?php

namespace App\Tests\Utility\Domestic;

use App\Entity\NotificationInterception;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Embeddable\LongAddress;
use App\Entity\SurveyStateInterface;
use App\Tests\Utility\AbstractNotificationInterceptionServiceTest;
use App\Utility\EmailHelper;

class NotificationInterceptionServiceTest extends AbstractNotificationInterceptionServiceTest
{
    public function dataDomesticIntercept(): array
    {
        return [
            [
                // Matches and no existing email
                null,
                '123 Test Road',
                'mango@example.com,banana@example.com'
            ],
            [
                // No match
                null,
                '456 Test Road',
                'mango@example.com,banana@example.com'
            ],
            [
                // Match but existing email
                'carrot@example.com',
                '123 Test Road',
                'mango@example.com,banana@example.com'
            ],
            [
                // No match
                'carrot@example.com',
                '456 Test Road',
                'mango@example.com,banana@example.com'
            ],
        ];
    }

    /**
     * @dataProvider dataDomesticIntercept
     */
    public function testDomesticIntercept(?string $surveyEmails, string $interceptAddressLine, string $interceptEmails): void
    {
        $survey = $this->addDomesticSurvey('123 Test Road', $surveyEmails);
        $this->addDomesticIntercept($interceptAddressLine, $interceptEmails);
        $this->entityManager->flush();

        $actualEmails = EmailHelper::convertArrayListToString($this->interceptService->getAllEmailsForSurvey($survey));
        $expectedEmails = $interceptAddressLine === $survey->getInvitationAddress()->getLine1()
            ? EmailHelper::convertArrayListToString([$surveyEmails, $interceptEmails])
            : $surveyEmails;
        $this->assertEquals($expectedEmails, $actualEmails);
    }

    protected function addDomesticIntercept(string $addressLine, string $emails): void {
        $intercept = (new NotificationInterception())
            ->addName($addressLine)
            ->setCsrgtEmails($emails);

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