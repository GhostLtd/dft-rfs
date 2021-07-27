<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\Address;
use App\Entity\Domestic\SurveyResponse;
use App\Tests\DataFixtures\SurveyFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\InitialDetailsDatabaseTestCase;

class InitialDetailsTestExtended extends AbstractWizardTest
{
    public function wizardData(): array
    {
        return [
            'In possession of vehicle' => [
                array_merge(
                    InitialDetailsData::initialDetailsData(SurveyResponse::IN_POSSESSION_YES),
                    [
                        new InitialDetailsDatabaseTestCase(
                            'Mark',
                            'mark@example.com',
                            SurveyResponse::IN_POSSESSION_YES,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                        ),
                    ],
                ),
            ],
            'Scrapped or stolen' => [
                array_merge(
                    InitialDetailsData::initialDetailsData(SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN),
                    [
                        new InitialDetailsDatabaseTestCase(
                            'Mark',
                            'mark@example.com',
                            SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN,
                            null,
                            null,
                            null,
                            new \DateTime('2021-3-12'),
                            null,
                            null,
                            null,
                        ),
                    ],
                ),
            ],
            'Sold' => [
                array_merge(
                    InitialDetailsData::initialDetailsData(SurveyResponse::IN_POSSESSION_SOLD),
                    [
                        new InitialDetailsDatabaseTestCase(
                            'Mark',
                            'mark@example.com',
                            SurveyResponse::IN_POSSESSION_SOLD,
                            null,
                            null,
                            null,
                            new \DateTime('2021-3-12'),
                            'Mark',
                            'mark@example.com',
                            (new Address())->setLine1('123 Road Road')->setPostcode('AB10 1AB'),
                        ),
                    ],
                ),
            ],
            'Vehicle on hire' => [
                array_merge(
                    InitialDetailsData::initialDetailsData(SurveyResponse::IN_POSSESSION_ON_HIRE),
                    [
                        new InitialDetailsDatabaseTestCase(
                            'Mark',
                            'mark@example.com',
                            SurveyResponse::IN_POSSESSION_ON_HIRE,
                            'Mark',
                            'mark@example.com',
                            (new Address())->setLine1('123 Road Road')->setPostcode('AB10 1AB'),
                            null,
                            null,
                            null,
                            null,
                        ),
                    ],
                ),
            ],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testInitialDetails($wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([SurveyFixtures::class]);
        $this->doWizardTest($browser, $wizardData);
    }
}