<?php

namespace App\Tests\Functional\Admin;

use App\Entity\Domestic\SurveyResponse as DomesticSurveyResponse;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\Domestic\ResponseFixtures;
use App\Tests\DataFixtures\Domestic\SurveyClosedFixtures;
use App\Tests\DataFixtures\International\ResponseStillActiveFixtures;

/**
 * Regression tests for the admin edit-final-details routes.
 *
 * The Domestic and International admin FinalDetailsType forms are dual-use: they serve as
 * the approve confirm-action form (where AbstractConfirmAction::getFormOptions() supplies
 * the required 'cancel_link_options') AND as the direct edit form on
 * /csrgt|irhs/surveys/{id}/edit-final-details — where the controller previously passed no
 * options, causing MissingOptionsException.
 */
class EditFinalDetailsTest extends AbstractAdminFunctionalTestCase
{
    public function testDomesticEditFinalDetailsRenders(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([SurveyClosedFixtures::class, ResponseFixtures::class]);

        $em = $this->getEntityManager();
        $surveyId = $em->getRepository(DomesticSurveyResponse::class)->findOneBy([])->getSurvey()->getId();

        $browser->request('GET', "/csrgt/surveys/{$surveyId}/edit-final-details");

        self::assertEquals(200, $browser->getResponse()->getStatusCode());
        // The cancel control is a link back to the survey view
        self::assertGreaterThanOrEqual(
            1,
            $browser->getCrawler()->filterXPath('//form//a')->count(),
            'Form should have a cancel link'
        );
    }

    public function testInternationalEditFinalDetailsRenders(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([ResponseStillActiveFixtures::class]);

        $em = $this->getEntityManager();
        /** @var InternationalSurvey $survey */
        $survey = $em->getRepository(InternationalSurvey::class)->findOneBy([]);
        $survey->setState(SurveyStateInterface::STATE_CLOSED);
        $em->flush();

        $browser->request('GET', "/irhs/surveys/{$survey->getId()}/edit-final-details");

        self::assertEquals(200, $browser->getResponse()->getStatusCode());
        self::assertGreaterThanOrEqual(
            1,
            $browser->getCrawler()->filterXPath('//form//a')->count(),
            'Form should have a cancel link'
        );
    }
}
