<?php

namespace App\Controller;

use App\Features;
use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * All /cron urls are secured by symfony's security access_control rules
 *
 * Class CronController
 * @package App\Controller
 * @Route("/cron")
 */
class CronController extends AbstractController
{
    /**
     * @Route("/messenger/consume")
     * @param Request $request
     * @param KernelInterface $kernel
     * @return Response
     * @throws Exception
     */
    public function messengerConsumer(Request $request, KernelInterface $kernel)
    {
        return $this->runCommand(
            $kernel,
            'messenger:consume',
            [
                '--limit' => 10,
                '--memory-limit' => '128M',
                '--time-limit' => 300,
                'receivers' => ['async'],
            ]
        );
    }

    private function runCommand(KernelInterface $kernel, $command, $options)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        unset($options['command']);
        $options = array_merge(['command' => $command], $options);
        $input = new ArrayInput($options);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        $content = $output->fetch();

        // return new Response(""), if you used NullOutput()
        return new Response($content);
    }
}
