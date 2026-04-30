<?php

namespace App\Tests\Unit\Workflow\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InitialDetailsStateTest extends KernelTestCase
{
    private FormWizardManager $formWizardManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->formWizardManager = self::getContainer()->get(FormWizardManager::class);
    }

    /**
     * @dataProvider dataIsValidAlternativeStartPlace
     */
    public function testIsValidAlternativeStartPlace(
        ?string $activityStatus,
        string $place,
        bool $expectedValid
    ): void {
        $response = new SurveyResponse();
        $response->setActivityStatus($activityStatus);

        // Set the ID using reflection to simulate a committed entity
        $idProperty = new ReflectionProperty(SurveyResponse::class, 'id');
        $idProperty->setValue($response, 'some-id');

        $stateObject = new InitialDetailsState();
        $stateObject->setSubject($response);

        $this->assertSame(
            $expectedValid,
            $this->formWizardManager->isValidAlternativeStartPlace($stateObject, $place),
            sprintf(
                'Expected place "%s" to be %s when activity status is "%s"',
                $place,
                $expectedValid ? 'valid' : 'invalid',
                $activityStatus ?? 'null'
            )
        );
    }

    public function dataIsValidAlternativeStartPlace(): array
    {
        return [
            // Still active - can access all change screens
            'still-active can access contact details' => [
                SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE,
                InitialDetailsState::STATE_CHANGE_CONTACT_DETAILS,
                true,
            ],
            'still-active can access activity status' => [
                SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE,
                InitialDetailsState::STATE_REQUEST_ACTIVITY_STATUS,
                true,
            ],
            'still-active can access business details' => [
                SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE,
                InitialDetailsState::STATE_REQUEST_BUSINESS_DETAILS,
                true,
            ],
            'still-active can access number of trips' => [
                SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE,
                InitialDetailsState::STATE_REQUEST_NUMBER_OF_TRIPS,
                true,
            ],

            // Ceased trading - cannot access business details or number of trips
            'ceased-trading can access contact details' => [
                SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING,
                InitialDetailsState::STATE_CHANGE_CONTACT_DETAILS,
                true,
            ],
            'ceased-trading can access activity status' => [
                SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING,
                InitialDetailsState::STATE_REQUEST_ACTIVITY_STATUS,
                true,
            ],
            'ceased-trading cannot access business details' => [
                SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING,
                InitialDetailsState::STATE_REQUEST_BUSINESS_DETAILS,
                false,
            ],
            'ceased-trading cannot access number of trips' => [
                SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING,
                InitialDetailsState::STATE_REQUEST_NUMBER_OF_TRIPS,
                false,
            ],

            // Only domestic work - cannot access business details or number of trips
            'only-domestic can access contact details' => [
                SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK,
                InitialDetailsState::STATE_CHANGE_CONTACT_DETAILS,
                true,
            ],
            'only-domestic can access activity status' => [
                SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK,
                InitialDetailsState::STATE_REQUEST_ACTIVITY_STATUS,
                true,
            ],
            'only-domestic cannot access business details' => [
                SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK,
                InitialDetailsState::STATE_REQUEST_BUSINESS_DETAILS,
                false,
            ],
            'only-domestic cannot access number of trips' => [
                SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK,
                InitialDetailsState::STATE_REQUEST_NUMBER_OF_TRIPS,
                false,
            ],

            // No road haulage - cannot access business details or number of trips
            'no-road-haulage can access contact details' => [
                SurveyResponse::ACTIVITY_STATUS_NO_ROAD_HAULAGE,
                InitialDetailsState::STATE_CHANGE_CONTACT_DETAILS,
                true,
            ],
            'no-road-haulage can access activity status' => [
                SurveyResponse::ACTIVITY_STATUS_NO_ROAD_HAULAGE,
                InitialDetailsState::STATE_REQUEST_ACTIVITY_STATUS,
                true,
            ],
            'no-road-haulage cannot access business details' => [
                SurveyResponse::ACTIVITY_STATUS_NO_ROAD_HAULAGE,
                InitialDetailsState::STATE_REQUEST_BUSINESS_DETAILS,
                false,
            ],
            'no-road-haulage cannot access number of trips' => [
                SurveyResponse::ACTIVITY_STATUS_NO_ROAD_HAULAGE,
                InitialDetailsState::STATE_REQUEST_NUMBER_OF_TRIPS,
                false,
            ],
        ];
    }

    public function testIsValidAlternativeStartPlaceReturnsFalseWhenNotCommitted(): void
    {
        $response = new SurveyResponse();
        // ID is null by default - not committed

        $stateObject = new InitialDetailsState();
        $stateObject->setSubject($response);

        $this->assertFalse(
            $this->formWizardManager->isValidAlternativeStartPlace($stateObject, InitialDetailsState::STATE_CHANGE_CONTACT_DETAILS)
        );
    }
}
