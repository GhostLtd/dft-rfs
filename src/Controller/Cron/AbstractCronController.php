<?php

namespace App\Controller\Cron;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CronController
 * @package App\Controller
 * @Route("/cron",
 *      condition="(request.headers.get('X-Appengine-Cron') === 'true') or (request.headers.get('X-Cloudscheduler') === 'true')"
 * )
 */
abstract class AbstractCronController extends AbstractController
{
    protected function runCommand(KernelInterface $kernel, $command, $options)
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
