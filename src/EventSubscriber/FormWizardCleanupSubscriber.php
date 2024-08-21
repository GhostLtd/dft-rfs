<?php

namespace App\EventSubscriber;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Workflow\FormWizardStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\WebProfilerBundle\Controller\ProfilerController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Clear all session-based wizard data from the session, except for the current wizard (if it is a wizard)
 */
class FormWizardCleanupSubscriber implements EventSubscriberInterface
{
    public function __construct(private RequestStack $requestStack, private LoggerInterface $log)
    {
    }

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
            $this->log->notice("[FormWizard] Ignoring controller: {$controllerClass}");
            return;
        }

        $this->log->notice("[FormWizard] Running on Controller: {$controllerClass}");

        if ($controller instanceof AbstractSessionStateWorkflowController) {
            // in wizard
            $this->cleanUp($controller->getSessionKey());
        } else {
            // not in wizard
            $this->cleanUp();
        }
        $this->log->notice("[FormWizard] Done");
    }

    private function cleanUp($exclude = null): void
    {
        $this->log->notice("[FormWizard] Exclude from search: {$exclude}");

        $session = $this->requestStack->getSession();
        $sessionVars = $session->all();
        foreach ($sessionVars as $key => $var) {
            if ($var instanceof FormWizardStateInterface && $key != $exclude) {
                $this->log->notice("[FormWizard] Removing wizard var: {$key}");
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