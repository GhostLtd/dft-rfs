<?php

namespace App\Tests\NewFunctional\Wizard\Admin;

abstract class AbstractAdminUploadTest extends AbstractAdminTest
{
    protected static ?string $fixtureDir;
    protected ?string $fixturePath;

    public static function setUpBeforeClass(): void
    {
        do {
            $timestamp = (new \DateTime())->format('Ymd-Hisu');
            self::$fixtureDir = sys_get_temp_dir() . "/tests-{$timestamp}/";
        } while (is_dir(self::$fixtureDir));
        mkdir(self::$fixtureDir);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$fixtureDir) {
            rmdir(self::$fixtureDir);
        }
        self::$fixtureDir = null;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->fixturePath) {
            unlink($this->fixturePath);
        }
        $this->fixturePath = null;
    }

    public function createFixture(string $filename, string $contents = ''): string
    {
        $this->fixturePath = self::$fixtureDir . $filename;
        file_put_contents($this->fixturePath, $contents);
        return $this->fixturePath;
    }
}
