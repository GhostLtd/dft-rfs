<?php

namespace App\Tests\MySQL;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Embeddable\LongAddress;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\UserFixtures;
use App\Utility\Domestic\OnHireStatsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * OnHireStatsProvider flags likely hire companies (admin survey list) with CAST(SUM(IF(...)) AS SIGNED): MySQL-only
 * DQL in production, so only this suite runs the query production does.
 */
class OnHireStatsProviderTest extends KernelTestCase
{
    use RunsOnMySql;

    public function testCountsSurveysWhoseVehicleWasOnHirePerCompany(): void
    {
        static::bootKernel();
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([UserFixtures::class]);
        $entityManager = $container->get(EntityManagerInterface::class);

        foreach ([
            ['Hire Co', SurveyResponse::IN_POSSESSION_ON_HIRE],
            ['Hire Co', SurveyResponse::IN_POSSESSION_ON_HIRE],
            ['Hire Co', SurveyResponse::IN_POSSESSION_YES],
            ['Haulier Ltd', SurveyResponse::IN_POSSESSION_YES],
            ['Haulier Ltd', null],
        ] as $i => [$companyName, $possession]) {
            $survey = (new Survey())
                ->setSurveyPeriodStart(new \DateTime('2021-05-03'))
                ->setSurveyPeriodEnd(new \DateTime('2021-05-09'))
                ->setIsNorthernIreland(false)
                ->setRegistrationMark("OH0{$i} ABC")
                ->setState(SurveyStateInterface::STATE_CLOSED)
                ->setInvitationAddress((new LongAddress())->setLine1($companyName)->setPostcode('AB1 2CD'));

            if ($possession !== null) {
                $response = (new SurveyResponse())->setIsInPossessionOfVehicle($possession);
                $survey->setResponse($response);
                $entityManager->persist($response);
            }
            $entityManager->persist($survey);
        }
        $entityManager->flush();

        $provider = $container->get(OnHireStatsProvider::class);

        $hireCo = $provider->getOnHireStats('Hire Co');
        $this->assertSame(2, $hireCo['onHireCount']);
        $this->assertSame(3, $hireCo['total']);
        $this->assertEqualsWithDelta(66.67, $hireCo['percentage'], 0.01);
        $this->assertTrue($provider->isLikelyHireCompany('Hire Co'));

        $haulier = $provider->getOnHireStats('Haulier Ltd');
        $this->assertSame(0, $haulier['onHireCount']);
        $this->assertSame(1, $haulier['total'], 'Only surveys with a response count');
        $this->assertFalse($provider->isLikelyHireCompany('Haulier Ltd'));
    }
}
