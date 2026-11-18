<?php

namespace App\Tests\NewFunctional\Wizard\Admin;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Action;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\International\Trip;
use App\Entity\PasscodeUser;
use App\Tests\DataFixtures\Dast\RoRoReferenceFixtures;
use App\Utility\Dev\DastSeeder;
use Symfony\Component\Routing\RouterInterface;

/**
 * The admin add/edit pages, requested by GET and by a POST without the form's fields, must never give a 500. Found by
 * DAST (local/2026-11-17-dast-bug-log.md): B1, controllers calling isValid() on a form that wasn't submitted (a POST
 * without its fields isn't a submission), and B3, the add-trip page reading an unset Trip::$roundTripDistance for
 * surveys that still have trip distances (period start before 2024-09-17, as the seeded one).
 *
 * Swept from the router, so new routes of these controllers are covered too. Uses the DAST seed data
 * (App\Utility\Dev\DastSeeder): a domestic survey with a day of stops (day 1) and a summary day (day 2), an
 * international survey with a vehicle, trip and actions, and a pre-enquiry.
 */
class AdminFormRobustnessTest extends AbstractAdminTestCase
{
    // The controllers with the B1 pattern (each must have a route swept)
    private const array CONTROLLERS = [
        'App\Controller\Admin\Domestic\DayStopController',
        'App\Controller\Admin\Domestic\DaySummaryController',
        'App\Controller\Admin\Domestic\SurveyAddController',
        'App\Controller\Admin\Domestic\SurveyController',
        'App\Controller\Admin\Domestic\SurveyResendController',
        'App\Controller\Admin\International\ActionController',
        'App\Controller\Admin\International\SurveyAddController',
        'App\Controller\Admin\International\SurveyController',
        'App\Controller\Admin\International\SurveyResendController',
        'App\Controller\Admin\International\TripController',
        'App\Controller\Admin\International\VehicleController',
        'App\Controller\Admin\PreEnquiry\AddController',
        'App\Controller\Admin\PreEnquiry\EditController',
        'App\Controller\Admin\PreEnquiry\ResendController',
    ];

    public function testNoAddOrEditPageGivesA500(): void
    {
        $this->initialiseTest([RoRoReferenceFixtures::class]);
        static::getContainer()->get(DastSeeder::class)->seed();
        // Reloaded from the database: the seeder doesn't fill the inverse sides (a day's stops) in memory
        $this->entityManager->clear();
        $ids = $this->ids();

        $failures = [];
        $swept = [];
        foreach (static::getContainer()->get(RouterInterface::class)->getRouteCollection() as $name => $route) {
            $controller = explode('::', (string) $route->getDefault('_controller'))[0];
            // Not the delete pages: a different (confirmation) flow, and deleting seeded records would break the rest
            if (!in_array($controller, self::CONTROLLERS, true) || str_ends_with($name, '_delete')) {
                continue;
            }

            $area = match (true) {
                str_starts_with($name, 'admin_domestic_') => 'domestic',
                str_starts_with($name, 'admin_international_') => 'international',
                str_starts_with($name, 'admin_preenquiry_') => 'pre-enquiry',
                default => throw new \LogicException("No area for {$name} (add one)"),
            };
            $parameters = [];
            foreach ($route->compile()->getPathVariables() as $variable) {
                $parameters[$variable] = $ids[$area][$variable]
                    ?? throw new \LogicException("No id for {$name}'s {$variable} (add one to ids())");
            }
            $path = (string) parse_url(static::getContainer()->get(RouterInterface::class)->generate($name, $parameters), PHP_URL_PATH);

            foreach (['GET', 'POST'] as $method) {
                $this->client->request($method, $path);
                $status = $this->client->getResponse()->getStatusCode();
                if ($status >= 500) {
                    $failures[] = "{$method} {$path} ({$name}) -> {$status}";
                }
            }
            $swept[$controller] = true;
        }

        self::assertSame([], array_values(array_diff(self::CONTROLLERS, array_keys($swept))), 'Controllers with no route swept');
        self::assertSame([], $failures, "500s:\n" . implode("\n", $failures));
    }

    /** @return array<string, array<string, string|int>> route parameter => value, per area */
    private function ids(): array
    {
        $domesticUser = $this->entityManager->getRepository(PasscodeUser::class)->findOneBy(['username' => DastSeeder::USERNAMES['domestic']]);
        $domesticSurvey = $domesticUser->getDomesticSurvey();
        self::assertInstanceOf(DomesticSurvey::class, $domesticSurvey);
        $days = $domesticSurvey->getResponse()->getDays();
        $stopDay = $days->filter(fn(Day $day) => $day->getNumber() === 1)->first();
        $summaryDay = $days->filter(fn(Day $day) => $day->getNumber() === 2)->first();

        $internationalUser = $this->entityManager->getRepository(PasscodeUser::class)->findOneBy(['username' => DastSeeder::USERNAMES['international']]);
        $internationalSurvey = $internationalUser->getInternationalSurvey();
        self::assertInstanceOf(InternationalSurvey::class, $internationalSurvey);
        $trip = $this->entityManager->getRepository(Trip::class)->findOneBy(['origin' => DastSeeder::MARKERS['international-trip']]);
        $action = $trip->getActions()->first();
        self::assertInstanceOf(Action::class, $action);

        $preEnquiry = $this->entityManager->getRepository(PasscodeUser::class)
            ->findOneBy(['username' => DastSeeder::USERNAMES['pre-enquiry']])->getPreEnquiry();

        return [
            'domestic' => [
                'surveyId' => $domesticSurvey->getId(),
                'originalSurveyId' => $domesticSurvey->getId(),
                'id' => $domesticSurvey->getId(),
                'dayId' => $stopDay->getId(),
                'stopId' => $stopDay->getStops()->first()->getId(),
                'summaryId' => $summaryDay->getSummary()->getId(),
                // A day with no entries, for the add-day pages
                'dayNumber' => 3,
            ],
            'international' => [
                'surveyId' => $internationalSurvey->getId(),
                'id' => $internationalSurvey->getId(),
                'vehicleId' => $trip->getVehicle()->getId(),
                'tripId' => $trip->getId(),
                'actionId' => $action->getId(),
            ],
            'pre-enquiry' => [
                'preEnquiryId' => $preEnquiry->getId(),
                'id' => $preEnquiry->getId(),
            ],
        ];
    }
}
