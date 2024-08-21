<?php

namespace App\Utility\RoRo;

use App\DTO\RoRo\OperatorRoute;
use App\Entity\RoRo\VehicleCount;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Entity\Route\Route;
use App\Entity\SurveyStateInterface;
use App\Repository\RoRo\CountryRepository;
use App\Repository\RoRo\SurveyRepository;
use App\Repository\Route\RouteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SurveyCreationHelper
{
    public function __construct(protected EntityManagerInterface $entityManager, protected LoggerInterface $logger, protected RouteRepository $routeRepository, protected SurveyRepository $surveyRepository, private CountryRepository $countryRepository)
    {
    }

    public function createSurveysForPreviousMonth(): void
    {
        // i.e. in May, we want to make April's survey
        [$currentYear, $previousMonth] = $this->getCurrentYearAndPreviousMonth();
        $this->logger->info("createSurveysForCurrentMonth: {currentYear}/{previousMonth}", [
            'currentYear' => $currentYear,
            'previousMonth' => $previousMonth
        ]);

        $surveyPeriodStart = $this->surveyRepository->getSurveyPeriodStartForYearAndMonth($currentYear, $previousMonth);
        $operatorRoutesWithMissingSurveys = $this->surveyRepository->getOperatorRoutesWithNoSurveysForSurveyPeriodStart($surveyPeriodStart);

        foreach($operatorRoutesWithMissingSurveys as $operatorRoute) {
            $survey = $this->createSurvey($surveyPeriodStart, $operatorRoute);
            $this->entityManager->persist($survey);
        }

        $this->entityManager->flush();
    }

    public function createSurveysForGivenOperatorAndRouteStartingAt(Operator $operator, Route $route, int $year, int $month): void
    {
        // i.e. if it's May, and we want to make surveys backdated to February, then we want to make February, March, April
        //      (but NOT May; that survey becomes available in June)
        [$currentYear, $previousMonth] = $this->getCurrentYearAndPreviousMonth();
        $this->logger->info("createSurveysForGivenOperatorAndRouteStartingAt: Operator {operatorId}, Route {routeId}, {fromYear}/{fromMonth} until {toYear}/{toMonth})", [
            'operatorId' => $operator->getId(),
            'routeId' => $route->getId(),
            'fromYear' => $year,
            'fromMonth' => $month,
            'toYear' => $currentYear,
            'toMonth' => $previousMonth,
        ]);

        if (!$operator->getRoutes()->contains($route)) {
            throw new \Exception('Route is not valid for given operator');
        }

        if ($year > $currentYear || ($year === $currentYear && $month > $previousMonth)) {
            throw new \Exception('Cannot create surveys in the future');
        }

        $start = $this->surveyRepository->getSurveyPeriodStartForYearAndMonth($year, $month);
        $end = $this->surveyRepository->getSurveyPeriodStartForYearAndMonth($currentYear, $previousMonth);

        $surveysInDateRange = $this->surveyRepository->getSurveysForOperatorRouteInDateRange($operator, $route, $start, $end);

        $surveyDates = [];
        foreach($surveysInDateRange as $survey) {
            $surveyDates[$survey->getSurveyPeriodStart()->format('Y/n')] = true; // N.B. n = non-zero-padded
        }

        $operatorRoute = new OperatorRoute($operator->getId(), $route->getId());
        for($y = $year; $y <= $currentYear; $y++) {
            $startMonth = ($y === $year) ? $month : 1;
            $endMonth = ($y === $currentYear) ? $previousMonth : 12;
            for($m = $startMonth; $m <= $endMonth; $m++) {
                if (!isset($surveyDates["$y/$m"])) {
                    $survey = $this->createSurvey($this->surveyRepository->getSurveyPeriodStartForYearAndMonth($y, $m), $operatorRoute);
                    $this->entityManager->persist($survey);
                }
            }
        }

        $this->entityManager->flush();
    }

    public function createSurvey(\DateTime $surveyPeriodStart, OperatorRoute $operatorRoute): Survey
    {
        $this->logger->info("Creating survey: {date} for route {route}, operator {operator}", [
            'date' => $surveyPeriodStart->format('Y/m'),
            'route' => $operatorRoute->getRouteId(),
            'operator' => $operatorRoute->getOperatorId(),
        ]);

        $survey = (new Survey())
            ->setSurveyPeriodStart($surveyPeriodStart)
            ->setState(SurveyStateInterface::STATE_NEW)
            ->setOperator($this->entityManager->getReference(Operator::class, $operatorRoute->getOperatorId()))
            ->setRoute($this->entityManager->getReference(Route::class, $operatorRoute->getRouteId()));

        foreach ($this->countryRepository->findAll() as $country) {
            $survey->addCountryVehicleCount((new VehicleCount())->setCountryCode($country->getCode()));
        }

        foreach ([VehicleCount::OTHER_CODE_OTHER, VehicleCount::OTHER_CODE_UNKNOWN, VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS] as $other) {
            $survey->addCountryVehicleCount((new VehicleCount())->setOtherCode($other));
        }

        return $survey;
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function getCurrentYearAndPreviousMonth(): array
    {
        $now = new \DateTime('1 month ago');
        return [
            intval($now->format('Y')),
            intval($now->format('n'))
        ];
    }
}