<?php


namespace App\EventSubscriber;


use App\Entity\Utility\MaintenanceLock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class MaintenanceLockSubscriber implements EventSubscriberInterface
{
    protected Environment $twig;
    protected RouterInterface $router;
    private EntityManagerInterface $entityManager;

    protected const ROUTE_WHITELIST = [
        'app_home_index',
        'app_home_sitemap',
        'app_home_accessibilitystatement',
    ];

    public function __construct(Environment $twig, RouterInterface $router, EntityManagerInterface $entityManager)
    {
        $this->twig = $twig;
        $this->router = $router;
        $this->entityManager = $entityManager;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$this->isMaintenanceMode($event)) {
            return;
        }

        $event->setResponse(new Response(
            $this->twig->render('bundles/TwigBundle/Exception/maintenance.html.twig'),
            200, // 200, not 503, so that AppEngine doesn't take instances out of service
            ['X-Robots-Tag' => 'noindex']
        ));
    }

    protected function isMaintenanceMode(RequestEvent $event): bool
    {
        if ($this->isWhitelistedRoute($event)) {
            return false;
        }

        try {
            $queryResult = $this->entityManager->createQueryBuilder()
                ->select('m.whitelistedIps')
                ->from(MaintenanceLock::class, 'm')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Throwable $e) {
            return false;
        }

        if (empty($queryResult)) {
            return false;
        }

        if (in_array($event->getRequest()->getClientIp(), json_decode($queryResult, true))) {
            return false;
        }

        return true;
    }

    protected function isWhitelistedRoute(RequestEvent $event): bool
    {
        $routeName = $event->getRequest()->attributes->get('_route');

        if ($routeName === '_wdt') {
            return true;
        }

        if (in_array($routeName, self::ROUTE_WHITELIST)) {
            return true;
        }

        return false;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => ['onKernelRequest', 20],
        ];
    }
}