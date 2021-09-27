<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use App\Tests\Functional\AbstractWizardTest;

class InitialDetailsTest extends AbstractWizardTest
{
    public function wizardData(): array
    {
        return [
            'In possession of vehicle' => [InitialDetailsData::initialDetailsData(SurveyResponse::IN_POSSESSION_YES)],
            'Scrapped or stolen' => [InitialDetailsData::initialDetailsData(SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN)],
            'Sold' => [InitialDetailsData::initialDetailsData(SurveyResponse::IN_POSSESSION_SOLD)],
            'Vehicle on hire' => [InitialDetailsData::initialDetailsData(SurveyResponse::IN_POSSESSION_ON_HIRE)],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testInitialDetailsWizard($wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([SurveyFixtures::class]);

        $this->doWizardTest($browser, $wizardData);
    }
}