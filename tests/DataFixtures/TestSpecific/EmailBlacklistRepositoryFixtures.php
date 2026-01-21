<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\Entity\EmailBlacklist;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EmailBlacklistRepositoryFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $addEntry = function(string $email, ?string $reason = null) use ($manager): void {
            $entry = (new EmailBlacklist())
                ->setEmail($email)
                ->setReason($reason)
                ->setCreatedAt(new \DateTime());

            $this->addReference('email-blacklist:' . strtolower($email), $entry);
            $manager->persist($entry);
        };

        $addEntry('blocked@example.com', 'Test block');
        $addEntry('UPPERCASE@EXAMPLE.COM', 'Case test');
        $addEntry('spammer@bad.com', 'Known spammer');

        $manager->flush();
    }
}
