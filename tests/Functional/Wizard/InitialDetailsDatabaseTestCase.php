<?php

namespace App\Tests\Functional\Wizard;

use App\Entity\Address;
use App\Entity\Domestic\SurveyResponse;
use App\Repository\Domestic\SurveyResponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class InitialDetailsDatabaseTestCase implements DatabaseTestCase
{
    public function __construct(protected ?string $contactName, protected ?string $contactEmail, protected ?string $isInPossessionOfVehicle, protected ?string $hireeName, protected ?string $hireeEmail, protected ?Address $hireeAddress, protected ?\DateTime $unableToCompleteDate, protected ?string $newOwnerName, protected ?string $newOwnerEmail, protected ?Address $newOwnerAddress)
    {
    }

    #[\Override]
    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $test): void
    {
        /** @var SurveyResponseRepository $repo */
        $repo = $entityManager->getRepository(SurveyResponse::class);
        $entityManager->clear();
        $responses = $repo->findAll();

        $test::assertCount(1, $responses, 'Expected a single surveyResponse to be in the database');

        $response = $responses[0];

        $test::assertEquals($this->contactName, $response->getContactName());
        $test::assertEquals($this->contactEmail, $response->getContactEmail());
        $test::assertEquals($this->isInPossessionOfVehicle, $response->getIsInPossessionOfVehicle());
        $test::assertEquals($this->hireeName, $response->getHireeName());
        $test::assertEquals($this->hireeEmail, $response->getHireeEmail());
        $test::assertEquals(
            $this->addressToString($this->hireeAddress),
            $this->addressToString($response->getHireeAddress())
        );
        $test::assertEquals(
            $this->dateToString($this->unableToCompleteDate),
            $this->dateToString($response->getUnableToCompleteDate())
        );
        $test::assertEquals($this->newOwnerName, $response->getNewOwnerName());
        $test::assertEquals($this->newOwnerEmail, $response->getNewOwnerEmail());
        $test::assertEquals(
            $this->addressToString($this->newOwnerAddress),
            $this->addressToString($response->getNewOwnerAddress())
        );
    }

    protected function addressToString(?Address $address): ?string
    {
        if ($address === null) {
            return null;
        }

        return $address->isFilled() ?
            join("\n", $address->toArray()) :
            null;
    }

    protected function dateToString(?\DateTimeInterface $date): ?string
    {
        return $date === null ? null : $date->format('Y-m-d');
    }
}