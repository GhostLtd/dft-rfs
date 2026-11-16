<?php

namespace App\Tests\MySQL;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\OperatorGroup;
use App\Repository\RoRo\OperatorGroupRepository;
use App\Repository\RoRo\OperatorRepository;
use App\Tests\DataFixtures\TestSpecific\OperatorGroupRepositoryFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A RoRo operator belongs to the group whose name prefixes its own: a raw DBAL join on SUBSTRING/LENGTH, compared
 * under production's utf8mb4_unicode_ci collation, which ignores case *and accents* (SQLite only lower-cases ASCII).
 */
class OperatorGroupMatchingTest extends KernelTestCase
{
    use RunsOnMySql;

    public static function dataOperatorNames(): array
    {
        return [
            'prefix' => ['Banana Boats', 'Banana'],
            'upper case' => ['BANANA FREIGHT', 'Banana'],
            'accented (unicode_ci ignores accents)' => ['Bänana Shipping', 'Banana'],
            'other group' => ['Apple Ferries', 'Apple'],
            'no group' => ['Cherry Ltd', null],
            'shorter than the group name' => ['Ban', null],
        ];
    }

    #[DataProvider('dataOperatorNames')]
    public function testFindsTheOperatorsGroup(string $operatorName, ?string $expectedGroup): void
    {
        static::bootKernel();
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([OperatorGroupRepositoryFixtures::class]);
        $entityManager = $container->get(EntityManagerInterface::class);

        $operator = (new Operator())->setName($operatorName)->setCode(42)->setIsActive(true);
        $entityManager->persist($operator);
        $entityManager->flush();

        $group = $container->get(OperatorRepository::class)->findOperatorGroupForOperator($operator);

        $this->assertSame($expectedGroup, $group?->getName());
    }

    public function testAccentedNamePrefixIsInUse(): void
    {
        static::bootKernel();
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([OperatorGroupRepositoryFixtures::class]);

        $repository = $container->get(EntityManagerInterface::class)->getRepository(OperatorGroup::class);
        $this->assertInstanceOf(OperatorGroupRepository::class, $repository);

        // Production treats "Bänana" as "Banana", so it can't be used as a new group's name either
        $this->assertTrue($repository->isNamePrefixAlreadyInUse('Bänana'));
    }
}
