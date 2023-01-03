<?php

namespace App\Twig;

use Symfony\Component\Workflow\Registry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WorkflowExtraExtension extends AbstractExtension
{
    private Registry $workflowRegistry;

    public function __construct(Registry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('workflow_can_any', [$this, 'canTransitionToAny']),
        ];
    }

    public function canTransitionToAny(object $subject, array $transitionNames, string $name = null): bool
    {
        $workflow = $this->workflowRegistry->get($subject, $name);

        foreach($transitionNames as $transitionName) {
            if ($workflow->can($subject, $transitionName)) {
                return true;
            }
        }

        return false;
    }
}