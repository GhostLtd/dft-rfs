<?php

namespace App\EventSubscriber;

use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\WebProfilerBundle\Controller\ProfilerController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Clear all session-based wizard data from the session, except for the current wizard (if it is a wizard).
 *
 * Bundle's FormWizardManager::cleanUp() does the same removal but is only invoked when a transition has a
 * redirect_route. This subscriber catches every controller event so stale wizard sessions don't accumulate
 * when the user navigates away mid-wizard.
 */
class FormWizardCleanupSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected RequestStack $requestStack,
        protected LoggerInterface $log
    ) {}

    public function kernelControllerEvent(ControllerEvent $event): void
    {
        $controllerBlacklist = [
            ProfilerController::class,
        ];

        if (!is_array($event->getController())) return;
        $controller = $event->getController()[0];
        $controllerClass = $controller::class;
        if (
            in_array($controllerClass, $controllerBlacklist)
            || !str_starts_with($controllerClass, "App\\Controller\\")
        ) {
            $this->log->debug("[FormWizard] Ignoring controller: {$controllerClass}");
            return;
        }

        $this->log->debug("[FormWizard] Running on Controller: {$controllerClass}");

        if ($controller instanceof AbstractSessionStateFormWizardController) {
            // in wizard
            $this->cleanUp($controller->getSessionKey());
        } else {
            // not in wizard
            $this->cleanUp();
        }
        $this->log->debug("[FormWizard] Done");
    }

    private function cleanUp($exclude = null): void
    {
        $this->log->debug("[FormWizard] Exclude from search: {$exclude}");

        $session = $this->requestStack->getSession();
        $sessionVars = $session->all();
        foreach ($sessionVars as $key => $var) {
            if ($var instanceof FormWizardStateInterface && $key != $exclude) {
                $this->log->debug("[FormWizard] Removing wizard var: {$key}");
                $session->remove($key);
            }
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['kernelControllerEvent', 256],
            ],
        ];
    }
}
