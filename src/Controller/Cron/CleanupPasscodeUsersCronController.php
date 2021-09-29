<?php

namespace App\Controller\Cron;

use App\Utility\RemindersHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class CleanupPasscodeUsersCronController extends AbstractCronController
{
    /**
     * @Route("/cleanup/passcode-users", name="cleanup_passcode_users")
     * @param RemindersHelper $remindersHelper
     * @param KernelInterface $kernel
     * @return Response
     */
    public function cleanupPasscodeUsersProcessor(Request $request, KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'app:cron:cleanup:passcode-users',
            []
        );
    }
}
