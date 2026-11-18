<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Route\UkPort;
use App\Security\Voter\PortVoter;
use App\Utility\RoRo\PortAndRouteUsageHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * The admin manager role's only effect (ROLE_ADMIN_MANAGER, from IapRoleResolver for management-domain emails): a
 * manager may edit a port that routes use; a plain admin may not. Deleting a port in use is refused to everyone.
 */
class PortVoterTest extends TestCase
{
    public static function cases(): array
    {
        $g = VoterInterface::ACCESS_GRANTED;
        $d = VoterInterface::ACCESS_DENIED;
        return [
            //                                   roles,                                    in use by route, by survey or route, expected
            'admin edits unused port' =>         [['ROLE_ADMIN_USER'], PortVoter::CAN_EDIT_PORT, false, false, $g],
            'admin edits port in use' =>         [['ROLE_ADMIN_USER'], PortVoter::CAN_EDIT_PORT, true, true, $d],
            'manager edits port in use' =>       [['ROLE_ADMIN_USER', 'ROLE_ADMIN_MANAGER'], PortVoter::CAN_EDIT_PORT, true, true, $g],
            'admin deletes unused port' =>       [['ROLE_ADMIN_USER'], PortVoter::CAN_DELETE_PORT, false, false, $g],
            'admin deletes port in use' =>       [['ROLE_ADMIN_USER'], PortVoter::CAN_DELETE_PORT, false, true, $d],
            'manager deletes port in use' =>     [['ROLE_ADMIN_USER', 'ROLE_ADMIN_MANAGER'], PortVoter::CAN_DELETE_PORT, true, true, $d],
            'non-admin edits unused port' =>     [[], PortVoter::CAN_EDIT_PORT, false, false, $d],
            'non-admin deletes unused port' =>   [[], PortVoter::CAN_DELETE_PORT, false, false, $d],
        ];
    }

    #[DataProvider('cases')]
    public function testVote(array $roles, string $attribute, bool $inUseByRoute, bool $inUseBySurveyOrRoute, int $expected): void
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(fn(string $role) => in_array($role, $roles, true));

        $usage = $this->createStub(PortAndRouteUsageHelper::class);
        $usage->method('isPortInUseByRoute')->willReturn($inUseByRoute);
        $usage->method('isPortInUseBySurveyOrRoute')->willReturn($inUseBySurveyOrRoute);

        $voter = new PortVoter($security, $usage);
        self::assertSame($expected, $voter->vote($this->createStub(TokenInterface::class), new UkPort(), [$attribute]));
    }
}
