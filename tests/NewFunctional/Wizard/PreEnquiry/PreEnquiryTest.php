<?php

namespace App\Tests\NewFunctional\Wizard\PreEnquiry;

use App\Entity\LongAddress;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\PreEnquiry\SurveyFixtures;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class PreEnquiryTest extends AbstractPreEnquiryTest
{
    public function testAcceptExistingCompanyNameAndAddress(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);
        $this->assertDatabaseHasNoResponse();

        $this->introPage();
        $this->companyName(true);
        $this->correspondenceDetails();
        $this->correspondenceAddress(true);
        $this->vehicleAndBusinessDetails();

        $data = [
            'companyName' => 'Wibble Sprockets Ltd',
            'totalVehicleCount' => 100,
            'internationalJourneyVehicleCount' => 50,
            'annualJourneyEstimate' => 200,
            'numberOfEmployees' => '10-49',
            'contactName' => 'Mark',
            'contactEmail' => 'mark@example.com',
            'contactTelephone' => '123456',
            'isCorrectAddress' => true,
            'isCorrectCompanyName' => true,
            'contactAddress' => [
                'line1' => 'Wibble Sprockets Ltd',
                'line2' => "30 Wibble Road",
                'line3' => "Wibbleston",
                'line4' => "Wibblesex",
                'line5' => null,
                'line6' => null,
                'postcode' => "WA10 1AB",
            ],
        ];

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);

        $this->formTestAction('/pre-enquiry', 'submit_pre_enquiry_submit', [
            new FormTestCase([]),
        ]);

        $this->assertDatabaseHasSurveyClosed();
    }

    public function testOverrideCompanyNameAndAddress(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);
        $this->assertDatabaseHasNoResponse();

        $this->introPage();
        $this->companyName(false, 'Badger sprockets ltd');

        $this->correspondenceDetails();

        $this->correspondenceAddress(
            false,
            'Two roady road',
            'Roadston',
            'West roadingly',
            null,
            null,
            'WO20 5WX',
        );

        $this->vehicleAndBusinessDetails();

        $data = [
            'companyName' => 'Badger sprockets ltd',
            'totalVehicleCount' => 100,
            'internationalJourneyVehicleCount' => 50,
            'annualJourneyEstimate' => 200,
            'numberOfEmployees' => '10-49',
            'contactName' => 'Mark',
            'contactEmail' => 'mark@example.com',
            'contactTelephone' => '123456',
            'isCorrectAddress' => false,
            'isCorrectCompanyName' => false,
            'contactAddress' => [
                'line1' => 'Badger sprockets ltd',
                'line2' => "Two roady road",
                'line3' => "Roadston",
                'line4' => "West roadingly",
                'line5' => null,
                'line6' => null,
                'postcode' => "WO20 5WX",
            ],
        ];

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);

        $this->formTestAction('/pre-enquiry', 'submit_pre_enquiry_submit', [
            new FormTestCase([]),
        ]);

        $this->assertDatabaseHasSurveyClosed();
    }

    protected function introPage(): void
    {
        $this->formTestAction('/pre-enquiry/introduction', 'form_continue', [
            new FormTestCase([]),
        ]);
    }

    protected function companyName(bool $isCorrectCompanyName, ?string $companyName = null): void
    {
        $formData = [
            'company_name[isCorrectCompanyName]' => $isCorrectCompanyName ? 'yes' : 'no',
        ];

        if ($companyName) {
            $formData['company_name[companyName]'] = $companyName;
        }

        $this->formTestAction('/pre-enquiry/company-name', 'company_name_continue', [
            new FormTestCase($formData),
        ]);
    }

    protected function correspondenceDetails(): void
    {
        $this->formTestAction('/pre-enquiry/correspondence-details', 'correspondence_details_continue', [
            new FormTestCase([
                'correspondence_details[contactName]' => 'Mark',
                'correspondence_details[contactTelephone]' => '123456',
                'correspondence_details[contactEmail]' => 'mark@example.com',
            ]),
        ]);
    }

    protected function correspondenceAddress(
        bool    $isCorrectAddress,
        ?string $addressLineTwo = null,
        ?string $addressLineThree = null,
        ?string $addressLineFour = null,
        ?string $addressLineFive = null,
        ?string $addressLineSix = null,
        ?string $postcode = null,
    ): void
    {
        $formData = [
            'correspondence_address[isCorrectAddress]' => $isCorrectAddress ? 'yes' : 'no',
        ];

        if ($addressLineTwo) {
            $formData['correspondence_address[contactAddress][lines][line2]'] = $addressLineTwo;
            $formData['correspondence_address[contactAddress][lines][line3]'] = $addressLineThree ?? '';
            $formData['correspondence_address[contactAddress][lines][line4]'] = $addressLineFour ?? '';
            $formData['correspondence_address[contactAddress][lines][line5]'] = $addressLineFive ?? '';
            $formData['correspondence_address[contactAddress][lines][line6]'] = $addressLineSix ?? '';
            $formData['correspondence_address[contactAddress][lines][postcode]'] = $postcode ?? '';
        }

        $this->formTestAction('/pre-enquiry/correspondence-address', 'correspondence_address_continue', [
            new FormTestCase($formData),
        ]);
    }

    protected function vehicleAndBusinessDetails(): void
    {
        $this->formTestAction('/pre-enquiry/vehicle-questions', 'vehicle_questions_continue', [
            new FormTestCase([
                'vehicle_questions[totalVehicleCount]' => '100',
                'vehicle_questions[internationalJourneyVehicleCount]' => '50',
                'vehicle_questions[annualJourneyEstimate]' => '200',
            ]),
        ]);

        $this->formTestAction('/pre-enquiry/business-details', 'business_details_continue', [
            new FormTestCase([
                'business_details[numberOfEmployees]' => '10-49',
            ]),
        ]);

        $this->pathTestAction('/pre-enquiry');
    }

    protected function assertDatabaseHasNoResponse(): void
    {
        $this->callbackTestAction(function (Context $context) {
            $test = $context->getTestCase();
            $survey = $this->getSurvey($context->getEntityManager(), $test);

            $test->assertNull($survey->getResponse());
        });
    }

    protected function assertDatabaseHasSurveyClosed(): void
    {
        $this->callbackTestAction(function (Context $context) {
            $test = $context->getTestCase();
            $survey = $this->getSurvey($context->getEntityManager(), $test);

            $test->assertEquals(SurveyStateInterface::STATE_CLOSED, $survey->getState());
        });
    }
}
