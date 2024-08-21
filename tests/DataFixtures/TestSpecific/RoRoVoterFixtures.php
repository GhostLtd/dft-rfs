<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\OperatorGroup;
use App\Entity\RoRo\Survey;
use App\Entity\RoRoUser;
use App\Entity\Route\Route;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\RoRo\OperatorRouteFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RoRoVoterFixtures extends Fixture implements DependentFixtureInterface
{
    protected int $currentCode = 800;

    protected ObjectManager $manager;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        // Group with two operators
        $group = $this->addOperatorGroup('Test');

        $this->addOperatorSurveyAndUser($group, 'Dover', SurveyStateInterface::STATE_NEW);
        $this->addOperatorSurveyAndUser($group, 'Portsmouth', SurveyStateInterface::STATE_CLOSED);

        // Operator with no group
        $this->addOperatorSurveyAndUser('Toast', 'Portsmouth', SurveyStateInterface::STATE_IN_PROGRESS);

        // Broken groups (shared/overlapping prefix)
        $this->addOperatorGroup('Illeg');
        $group = $this->addOperatorGroup('IllegalGroup');

        $this->addOperatorSurveyAndUser($group, 'Broken', SurveyStateInterface::STATE_IN_PROGRESS);

        $manager->flush();
    }

    protected function addOperatorGroup(string $name): OperatorGroup {
        $refName = $this->getRefName($name);

        $group = (new OperatorGroup())
            ->setName($name);

        $this->manager->persist($group);
        $this->addReference("operator-group:{$refName}", $group);

        return $group;
    }

    protected function addOperatorSurveyAndUser(OperatorGroup|string $operatorGroup, string $name, string $surveyState): void {
        $groupName = ($operatorGroup instanceof OperatorGroup) ?
            $operatorGroup->getName() :
            $operatorGroup;

        $operatorName = "{$groupName} - {$name}";
        $refName = $this->getRefName($groupName) . "-" . $this->getRefName($name);

        $operator = (new Operator())
            ->setName($operatorName)
            ->setCode($this->currentCode++)
            ->setIsActive(true);

        $user = (new RoRoUser())
            ->setUsername("{$refName}@example.com")
            ->setOperator($operator);

        $this->manager->persist($operator);
        $this->manager->persist($user);

        $this->addReference("operator:{$refName}", $operator);
        $this->addReference("user:{$refName}", $user);

        $route = $this->getReference('roro:route:1', Route::class);
        $operator->addRoute($route);
        $operator->addUser($user);

        $survey = (new Survey())
            ->setOperator($operator)
            ->setRoute($route)
            ->setState($surveyState)
            ->setSurveyPeriodStart(new \DateTime());

        $this->addReference("survey:{$refName}", $survey);
        $this->manager->persist($survey);
    }

    protected function getRefName(string $name): string
    {
        return str_replace(' ', '-', strtolower($name));
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            OperatorRouteFixtures::class
        ];
    }
}