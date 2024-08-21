<?php

namespace App\Tests\Utility\International;

use App\Entity\International\Company;
use App\Entity\International\NotificationInterception as InternationalIntercept;
use App\Entity\International\NotificationInterceptionCompanyName;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\LongAddress;
use App\Entity\SurveyStateInterface;
use App\Tests\Utility\AbstractNotificationInterceptionServiceTest;

class NotificationInterceptionServiceTest extends AbstractNotificationInterceptionServiceTest
{
    public function dataInternationalIntercept(): array
    {
        return [
            // Matches
            [
                false, // No existing email nor address
                null,
                null,
                ['Widgets Ltd'],
                'mango@example.com'
            ],
            [
                true, // No existing email
                '123 Test Road',
                null,
                ['Widgets Ltd'],
                'mango@example.com'
            ],
            [
                false, // Existing email
                null,
                'apple@example.com',
                ['Widgets Ltd'],
                'mango@example.com'
            ],
            [
                false, // Existing email and address
                '123 Test Road',
                'apple@example.com',
                ['Widgets Ltd'],
                'mango@example.com'
            ],

            // Same variations, but with no match
            [
                false,
                null,
                null,
                ['Badger Incorporated'],
                'mango@example.com'
            ],
            [
                false,
                '123 Test Road',
                null,
                ['Badger Incorporated'],
                'mango@example.com'
            ],
            [
                false,
                null,
                'apple@example.com',
                ['Badger Incorporated'],
                'mango@example.com'
            ],
            [
                false,
                '123 Test Road',
                'apple@example.com',
                ['Badger Incorporated'],
                'mango@example.com'
            ],

            // Same variations but with matches in the secondary names
            [
                false,
                null,
                null,
                ['Badger Incorporated', 'Spanner Ensemble', 'Widgets Ltd'],
                'mango@example.com'
            ],
            [
                true,
                '123 Test Road',
                null,
                ['Badger Incorporated', 'Spanner Ensemble', 'Widgets Ltd'],
                'mango@example.com'
            ],
            [
                false,
                null,
                'apple@example.com',
                ['Badger Incorporated', 'Spanner Ensemble', 'Widgets Ltd'],
                'mango@example.com'
            ],
            [
                false,
                '123 Test Road',
                'apple@example.com',
                ['Badger Incorporated', 'Spanner Ensemble', 'Widgets Ltd'],
                'mango@example.com'
            ],
        ];
    }

    /**
     * @dataProvider dataInternationalIntercept
     */
    public function testInternationalIntercept(bool $expectedToChange, ?string $addressLine1, ?string $surveyEmails, array $interceptCompanyNames, string $interceptEmails): void
    {
        $survey = $this->addInternationalSurvey('Widgets Ltd', $addressLine1, $surveyEmails);
        $this->addInternationalIntercept($interceptCompanyNames, $interceptEmails);
        $this->entityManager->flush();

        $this->interceptService->checkAndInterceptSurvey($survey);

        $expectedEmails = $expectedToChange ? $interceptEmails : $surveyEmails;
        $this->assertEquals($expectedEmails, $survey->getInvitationEmails());
    }

    protected function addInternationalIntercept(array $companyNames, string $emails): void {
        $primaryName = array_shift($companyNames);

        $intercept = (new InternationalIntercept())
            ->setPrimaryName($primaryName)
            ->setEmails($emails);

        $this->entityManager->persist($intercept);

        foreach($companyNames as $companyName) {
            $additionalIntercept = (new NotificationInterceptionCompanyName())
                ->setName($companyName)
                ->setNotificationInterception($intercept);

            $this->entityManager->persist($additionalIntercept);
        }
    }

    protected function addInternationalSurvey(string $companyName, ?string $addressLine1, ?string $invitationEmails): InternationalSurvey {
        $company = (new Company())
            ->setBusinessName($companyName);

        if ($addressLine1) {
            $address = (new LongAddress())
                ->setLine1($addressLine1);
        } else {
            $address = null;
        }

        $survey = (new InternationalSurvey())
            ->setCompany($company)
            ->setReferenceNumber('Test123')
            ->setInvitationAddress($address)
            ->setInvitationEmails($invitationEmails)
            ->setState(SurveyStateInterface::STATE_IN_PROGRESS);

        $this->entityManager->persist($company);
        $this->entityManager->persist($survey);

        return $survey;
    }
}
