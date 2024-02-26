<?php

namespace App\Tests\DataFixtures\RoRo;

use App\DTO\RoRo\OperatorRoute;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Entity\Route\Route;
use App\Utility\RoRo\SurveyCreationHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SurveyFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(protected SurveyCreationHelper $surveyCreationHelper)
    {}

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $manager->persist($this->createSurvey('roro:route:3', 'roro:operator:1', '2020-08-01', 'roro:survey:1'));
        $manager->persist($this->createSurvey('roro:route:2', 'roro:operator:1', '2020-08-01', 'roro:survey:2'));
        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            CountryFixtures::class,
            OperatorRouteFixtures::class,
            UserFixtures::class,
        ];
    }

    public function createSurvey(string $routeRef, string $operatorRef, string $date, string $surveyRef): Survey
    {
        $route = $this->getReference($routeRef, Route::class);
        $operator = $this->getReference($operatorRef, Operator::class);

        $operatorRoute = new OperatorRoute($operator->getId(), $route->getId());

        $survey = $this->surveyCreationHelper->createSurvey(new \DateTime($date), $operatorRoute);
        $this->setReference($surveyRef, $survey);

        return $survey;
    }
}