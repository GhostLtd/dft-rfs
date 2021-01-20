<?php


namespace App\EventSubscriber;


use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Workflow\FormWizardStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\WebProfilerBundle\Controller\ProfilerController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Clear all session-based wizard data from the session, except for the current wizard (if it is a wizard)
 *
 * Class FormWizardCleanupSubscriber
 * @package App\EventSubscriber
 */
class FormWizardCleanupSubscriber implements EventSubscriberInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var LoggerInterface
     */
    private $log;

    public function __construct(SessionInterface $session, LoggerInterface $log)
    {
        $this->session = $session;
        $this->log = $log;
    }

    public function kernelControllerEvent(ControllerEvent $event)
    {
        $controllerBlacklist = [
            ProfilerController::class,
        ];

        if (!is_array($event->getController())) return;
        $controller = $event->getController()[0];
        $controllerClass = get_class($controller);
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

    private function cleanUp($exclude = null)
    {
        $this->log->notice("[FormWizard] Exclude from search: {$exclude}");

        $sessionVars = $this->session->all();
        foreach ($sessionVars as $key => $var) {
            if ($var instanceof FormWizardStateInterface && $key != $exclude) {
                $this->log->notice("[FormWizard] Removing wizard var: {$key}");
                $this->session->remove($key);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => [
                ['kernelControllerEvent', 256],
            ],
        ];
    }
}