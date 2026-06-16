<?php

namespace App\EventSubscriber;

use App\Attribute\MaintenanceWarningBanner;
use App\Repository\MaintenanceWarningRepository;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MaintenanceWarningBannerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MaintenanceWarningRepository $maintenanceWarningRepository,
    ) {}

    public function onControllerArguments(ControllerArgumentsEvent $event): void
    {
        if (!$event->isMainRequest() || !$event->getAttributes(MaintenanceWarningBanner::class)) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->isMethod('GET') || !$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            return;
        }

        $banner = $this->maintenanceWarningRepository->getNotificationBanner();
        if ($banner === null) {
            return;
        }

        $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, $banner);
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => 'onControllerArguments',
        ];
    }
}
