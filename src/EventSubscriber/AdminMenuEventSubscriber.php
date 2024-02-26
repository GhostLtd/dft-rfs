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
    public function __construct(private Environment $twig, private AdminMenu $adminMenu, private string $hostname = '')
    {
    }

    public function onKernelController(ControllerEvent $event)
    {
        if ($event->getRequest()->getHost() === $this->hostname) {
            $this->twig->addGlobal('menu', $this->adminMenu->getMenuItems());
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}
