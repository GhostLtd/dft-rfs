<?php

namespace App\Tests\NewFunctional\Wizard\PreEnquiry;

use App\Tests\DataFixtures\PreEnquiry\ResponseFixtures;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class PreEnquiryChangeTest extends AbstractPreEnquiryTest
{
    public function testChangeCompanyNameIncorrect(): void
    {
        $this->performChangeTest(0, function(array &$expectedData) {
            $this->formTestAction('/pre-enquiry/company-name', 'company_name_continue', [
                new FormTestCase([
                    'company_name[isCorrectCompanyName]' => 'no',
                    'company_name[companyName]' => 'Dabble Sockets Ltd',
                ]),
            ]);

            $expectedData['companyName'] = $expectedData['contactAddress']['line1'] = 'Dabble Sockets Ltd';
            $expectedData['isCorrectCompanyName'] = false;
        });
    }

    public function testChangeCompanyNameCorrect(): void
    {
        $this->performChangeTest(0, function(array &$expectedData) {
            $this->formTestAction('/pre-enquiry/company-name', 'company_name_continue', [
                new FormTestCase([
                    'company_name[isCorrectCompanyName]' => 'yes',
                ]),
            ]);

            $expectedData['companyName'] = $expectedData['contactAddress']['line1'] = 'Wibble Sprockets Ltd';
            $expectedData['isCorrectCompanyName'] = true;
        });
    }

    public function testVehicleDetailsChange(): void
    {
        $this->performChangeTest(1, function(array &$expectedData) {
            $this->formTestAction('/pre-enquiry/vehicle-questions', 'vehicle_questions_continue', [
                new FormTestCase([
                    'vehicle_questions[totalVehicleCount]' => '123',
                    'vehicle_questions[internationalJourneyVehicleCount]' => '22',
                    'vehicle_questions[annualJourneyEstimate]' => '303',
                ]),
            ]);

            $expectedData['totalVehicleCount'] = '123';
            $expectedData['internationalJourneyVehicleCount'] = '22';
            $expectedData['annualJourneyEstimate'] = '303';
        });
    }

    public function testNumberOfEmployeesChange(): void
    {
        $this->performChangeTest(4, function(array &$expectedData) {
            $this->formTestAction('/pre-enquiry/business-details', 'business_details_continue', [
                new FormTestCase([
                    'business_details[numberOfEmployees]' => '10001-30000',
                ]),
            ]);

            $expectedData['numberOfEmployees'] = '10001-30000';
        });
    }

    public function testCorrespondenceDetailsChange(): void
    {
        $this->performChangeTest(5, function(array &$expectedData) {
            $this->formTestAction('/pre-enquiry/correspondence-details', 'correspondence_details_continue', [
                new FormTestCase([
                    'correspondence_details[contactName]' => 'Charlie',
                    'correspondence_details[contactTelephone]' => '07700 900999',
                    'correspondence_details[contactEmail]' => 'charlie@example.com',
                ]),
            ]);

            $expectedData['contactName'] = 'Charlie';
            $expectedData['contactTelephone'] = '07700 900999';
            $expectedData['contactEmail'] = 'charlie@example.com';
        });
    }

    public function testChangeCompanyAddressIncorrect(): void
    {
        $this->performChangeTest(8, function(array &$expectedData) {
            $this->formTestAction('/pre-enquiry/correspondence-address', 'correspondence_address_continue', [
                new FormTestCase([
                    'correspondence_address[isCorrectAddress]' => 'no',
                    'correspondence_address[contactAddress][lines][line2]' => 'Apple',
                    'correspondence_address[contactAddress][lines][line3]' => 'Banana',
                    'correspondence_address[contactAddress][lines][line4]' => 'Cabbage',
                    'correspondence_address[contactAddress][lines][line5]' => 'Dill',
                    'correspondence_address[contactAddress][lines][line6]' => 'Endive',
                    'correspondence_address[contactAddress][lines][postcode]' => 'FO20 1AB',
                ]),
            ]);

            $expectedData['isCorrectAddress'] = false;
            $expectedData['contactAddress']['line2'] = 'Apple';
            $expectedData['contactAddress']['line3'] = 'Banana';
            $expectedData['contactAddress']['line4'] = 'Cabbage';
            $expectedData['contactAddress']['line5'] = 'Dill';
            $expectedData['contactAddress']['line6'] = 'Endive';
            $expectedData['contactAddress']['postcode'] = 'FO20 1AB';
        });
    }

    public function testChangeCompanyAddressCorrect(): void
    {
        $this->performChangeTest(8, function(array &$expectedData) {
            $this->formTestAction('/pre-enquiry/correspondence-address', 'correspondence_address_continue', [
                new FormTestCase([
                    'correspondence_address[isCorrectAddress]' => 'yes',
                ]),
            ]);

            $expectedData['isCorrectAddress'] = true;
            $expectedData['contactAddress']['line2'] = '30 Wibble Road';
            $expectedData['contactAddress']['line3'] = 'Wibbleston';
            $expectedData['contactAddress']['line4'] = 'Wibblesex';
            $expectedData['contactAddress']['line5'] = '';
            $expectedData['contactAddress']['line6'] = '';
            $expectedData['contactAddress']['postcode'] = 'WA10 1AB';
        });
    }

    protected function performChangeTest(int $linkIndex, \Closure $callback): void
    {
        $this->initialiseTest([ResponseFixtures::class]);

        $data = $this->getInitialData();

        $this->pathTestAction('/pre-enquiry');
        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);

        $this->clickLinkContaining('Change', $linkIndex);

        $callback($data);

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    protected function getInitialData(): array
    {
        return [
            'companyName' => 'Wabble Sockets Ltd',
            'totalVehicleCount' => '70',
            'internationalJourneyVehicleCount' => '20',
            'annualJourneyEstimate' => '51',
            'numberOfEmployees' => '10-49',
            'contactName' => 'Bob Bobbington',
            'contactEmail' => 'bob@example.com',
            'contactTelephone' => '8118181',
            'isCorrectAddress' => false,
            'isCorrectCompanyName' => false,
            'contactAddress' => [
                'line1' => 'Wabble Sockets Ltd',
                'line2' => '40 Dibble Road',
                'line3' => 'Dibbleston',
                'line4' => 'Dibblesex',
                'line5' => '',
                'line6' => '',
                'postcode' => 'DA10 1AB',
            ],
        ];
    }
}
