<?php

namespace App\Tests\NewFunctional\Wizard\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTest;
use App\Tests\NewFunctional\Wizard\Action\Context;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractPreEnquiryTest extends AbstractPasscodeWizardTest
{
    protected function getSurvey(EntityManagerInterface $entityManager, TestCase $test): PreEnquiry
    {
        $repository = $entityManager->getRepository(PreEnquiry::class);

        $entityManager->clear();
        $surveys = $repository->findAll();

        $test->assertCount(1, $surveys, 'Expected a single survey to be in the database');

        $survey = $surveys[0];
        $test->assertInstanceOf(PreEnquiry::class, $survey);

        return $survey;
    }

    protected function assertDashboardMatchesData(array $data): void
    {
        $addressWithoutLine1 = $data['contactAddress'];
        unset($addressWithoutLine1['line1']);

        $address = array_filter($addressWithoutLine1, fn(?string $x) => $x !== '' && $x !== null);
        $address = join(",\n", $address);

        $dashboardData = [
            'Company name' => $data['companyName'],
            'Total HGV count' => $data['totalVehicleCount'],
            'Vehicles used' => $data['internationalJourneyVehicleCount'],
            'International journey' => $data['annualJourneyEstimate'],
            'Number of employees' => $data['numberOfEmployees'],
            'Name' => $data['contactName'],
            'Email' => $data['contactEmail'],
            'Telephone number' => $data['contactTelephone'],
            'Address' => $address,
        ];

        $this->assertSummaryListData($dashboardData);
    }

    protected function assertDatabaseMatchesData(array $data): void
    {
        $this->callbackTestAction(function (Context $context) use ($data) {
            $test = $context->getTestCase();
            $survey = $this->getSurvey($context->getEntityManager(), $test);

            $this->assertDataMatches($survey->getResponse(), $data, 'response');
        });
    }
}
