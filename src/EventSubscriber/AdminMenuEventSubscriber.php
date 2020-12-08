<?php

namespace App\EventSubscriber;

use App\Utility\Menu\AdminMenu;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class AdminMenuEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var AdminMenu
     */
    private $adminMenu;


    public function __construct(Environment $twig, AdminMenu $adminMenu, $hostname = '')
    {
        $this->twig = $twig;
        $this->adminMenu = $adminMenu;
        $this->hostname = $hostname;
    }

    public function onKernelController(ControllerEvent $event)
    {
        if ($event->getRequest()->getHost() === $this->hostname) {
            $this->twig->addGlobal('menu', $this->adminMenu->getMenuItems());
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}
