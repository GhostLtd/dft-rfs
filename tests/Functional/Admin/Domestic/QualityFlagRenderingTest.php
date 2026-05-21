<?php

namespace App\Tests\Functional\Admin\Domestic;

use App\Controller\Admin\Domestic\SurveyController;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\Survey;
use App\Entity\Embeddable\Distance;
use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Tests\DataFixtures\Domestic\SurveyClosedFixtures;
use App\Tests\Functional\Admin\AbstractAdminFunctionalTest;

/**
 * End-to-end: when several stops on a day share a quality-check reason, the survey view renders a
 * single grouped tag ("Stops 1,2: ...") rather than one tag per stop.
 */
class QualityFlagRenderingTest extends AbstractAdminFunctionalTest
{
    public function testRepeatedStopReasonRendersAsOneGroupedTag(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([SurveyClosedFixtures::class, DayStopFixtures::class]);

        // Make both of the day's stops zero-distance so they share the one reason.
        $em = $this->getEntityManager();
        foreach ($em->getRepository(DayStop::class)->findAll() as $stop) {
            $stop->setDistanceTravelled((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(0));
        }
        $em->flush();

        $surveyId = $em->getRepository(Survey::class)->findOneBy([])->getId();
        $browser->request('GET', $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $surveyId]));

        $content = $browser->getResponse()->getContent();
        self::assertEquals(200, $browser->getResponse()->getStatusCode());

        // One grouped tag, not two per-stop tags.
        self::assertStringContainsString('Stops 1,2: No distance recorded', $content);
        self::assertStringNotContainsString('Stop 1: No distance recorded', $content);
        self::assertSame(1, substr_count($content, 'No distance recorded'));
    }

    private function generateUrl(string $route, array $params): string
    {
        return static::getContainer()->get('router')->generate($route, $params);
    }
}
