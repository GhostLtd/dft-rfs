<?php

namespace App\Tests\NewFunctional\Wizard\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTestCase;
use App\Tests\NewFunctional\Wizard\Action\Context;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractPreEnquiryTestCase extends AbstractPasscodeWizardTestCase
{
    protected function getSurvey(EntityManagerInterface $entityManager, TestCase $test): PreEnquiry
    {
        $repository = $entityManager->getRepository(PreEnquiry::class);

        $entityManager->clear();
        $surveys = $repository->findAll();

        $test->assertCount(1, $surveys, 'Expected a single survey to be in the database');

        $survey = $surveys[0];

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
            'Total HGV count' => $data['totalHgvCount'],
            'HGVs used' => $data['internationalJourneyHgvCount'],
            'International HGV journeys' => $data['annualHgvJourneyEstimate'],
            'Total LGV count' => $data['totalLgvCount'],
            'LGVs used' => $data['internationalJourneyLgvCount'],
            'International LGV journeys' => $data['annualLgvJourneyEstimate'],
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
