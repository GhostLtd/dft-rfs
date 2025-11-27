<?php

namespace App\Tests\Unit\Workflow\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class InitialDetailsStateTest extends TestCase
{
    /**
     * @dataProvider dataIsValidAlternativeStartState
     */
    public function testIsValidAlternativeStartState(
        ?string $activityStatus,
        string $state,
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
            $stateObject->isValidAlternativeStartState($state),
            sprintf(
                'Expected state "%s" to be %s when activity status is "%s"',
                $state,
                $expectedValid ? 'valid' : 'invalid',
                $activityStatus ?? 'null'
            )
        );
    }

    public function dataIsValidAlternativeStartState(): array
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

    public function testIsValidAlternativeStartStateReturnsFalseWhenNotCommitted(): void
    {
        $response = new SurveyResponse();
        // ID is null by default - not committed

        $stateObject = new InitialDetailsState();
        $stateObject->setSubject($response);

        $this->assertFalse(
            $stateObject->isValidAlternativeStartState(InitialDetailsState::STATE_CHANGE_CONTACT_DETAILS)
        );
    }
}
