<?php

namespace App\Tests\Utility\Rehearsal;

use App\Utility\RegistrationMarkHelper;
use App\Utility\Rehearsal\Scenario;
use App\Utility\Rehearsal\ScenarioCatalogue;
use PHPUnit\Framework\TestCase;

class ScenarioCatalogueTest extends TestCase
{
    public function testTheSpecScenariosInOrder(): void
    {
        self::assertSame([
            'csrgt:invite', 'irhs:invite', 'pre:invite',
            'csrgt:r1-due', 'csrgt:r2-due', 'csrgt:r1-email-only', 'csrgt:r1-period-not-ended',
            'irhs:r1-due', 'irhs:r2-due', 'irhs:r1-email-only', 'irhs:r1-period-not-ended',
            'pre:r1-due', 'pre:r2-due',
            'roro:r1-due', 'roro:r2-due',
            'ip:r1-on-from', 'ip:r1-sms', 'ip:r1-landline', 'ip:r1-no-contact', 'ip:r2-due', 'ip:r3-due',
            'ip:chased-recently', 'ip:manual-recently', 'ip:closed', 'ip:before-from',
        ], array_map(fn(Scenario $s) => $s->name, ScenarioCatalogue::all()));
    }

    public function testRegistrationMarksFollowTheSpecTable(): void
    {
        $marks = [];
        foreach (ScenarioCatalogue::all() as $s) {
            if ($s->registrationMark !== null) {
                $marks[$s->name] = $s->registrationMark;
                $helper = new RegistrationMarkHelper($s->registrationMark);
                self::assertTrue($helper->isValid() && $helper->isCurrent(), "{$s->registrationMark} must be a valid current-format mark");
            }
        }
        self::assertSame([
            'csrgt:invite' => 'SD00NAA', 'csrgt:r1-due' => 'SD00NAB', 'csrgt:r2-due' => 'SD00NAC',
            'csrgt:r1-email-only' => 'SD00NAD', 'csrgt:r1-period-not-ended' => 'SD00NAE',
            'ip:r1-on-from' => 'SD00PAA', 'ip:r1-sms' => 'SD00PAB', 'ip:r1-landline' => 'SD00PAC',
            'ip:r1-no-contact' => 'SD00PAD', 'ip:r2-due' => 'SD00PAE', 'ip:r3-due' => 'SD00PAF',
            'ip:chased-recently' => 'SD00PAG', 'ip:manual-recently' => 'SD00PAH', 'ip:closed' => 'SD00PAJ',
            'ip:before-from' => 'SD00PAK',
        ], $marks);
    }

    public function testContactsAreUniqueAndFake(): void
    {
        $emails = array_map(fn(Scenario $s) => $s->email(), ScenarioCatalogue::all());
        self::assertSame($emails, array_unique($emails));
        foreach ($emails as $email) {
            self::assertStringEndsWith('@example.invalid', $email);
        }

        $mobiles = array_values(array_filter(array_map(fn(Scenario $s) => $s->telephone, ScenarioCatalogue::all()), fn($t) => $t !== null && str_starts_with($t, '07')));
        self::assertSame($mobiles, array_unique($mobiles));
        foreach ($mobiles as $mobile) {
            self::assertNotContains(substr(str_replace(' ', '', $mobile), -3), ['000', '111', '222', '002', '003']);
        }
    }
}
