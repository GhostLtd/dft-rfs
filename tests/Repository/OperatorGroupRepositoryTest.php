<?php

namespace App\Tests\Repository;

use App\Entity\RoRo\OperatorGroup;
use App\Repository\RoRo\OperatorGroupRepository;
use App\Tests\DataFixtures\TestSpecific\OperatorGroupRepositoryFixtures;

class OperatorGroupRepositoryTest extends AbstractRepositoryTest
{
    public function dataIsNamePrefixAlreadyInUse(): array
    {
        return [
            ['ban', true],
            ['BAN', true],
            ['coco', false],
            ['banana', true],
            ['banana one', true],
            ['bani', false],

            ['ban', false, 'operator-group:banana'],
            ['banana one', false, 'operator-group:banana'],
            ['coco', false, 'operator-group:banana'],
            ['ban', true, 'operator-group:apple'],
        ];
    }

    /**
     * @dataProvider dataIsNamePrefixAlreadyInUse
     */
    public function testIsNamePrefixAlreadyInUse(string $name, bool $expectedToBeInUse, ?string $excludingGroupRef = null): void
    {
        $this->loadFixtures([OperatorGroupRepositoryFixtures::class]);
        $repo = $this->getRepository(OperatorGroup::class);

        $this->assertInstanceOf(OperatorGroupRepository::class, $repo);

        $excludingGroup = $excludingGroupRef ?
            $this->fixtureReferenceRepository->getReference($excludingGroupRef, OperatorGroup::class) :
            null;

        $this->assertEquals($expectedToBeInUse, $repo->isNamePrefixAlreadyInUse($name, $excludingGroup));
    }
}
