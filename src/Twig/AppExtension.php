<?php

namespace App\Twig;

use App\Controller\InternationalPreEnquiry\InternationalPreEnquiryController;
use App\Workflow\InternationalPreEnquiry\PreEnquiryState;
use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    protected $iconsDir;

    protected $router;

    public function __construct(KernelInterface $kernel, RouterInterface $router) {
        $projectDir = $kernel->getProjectDir();
        $this->iconsDir = "$projectDir/assets/icons";
        $this->router = $router;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('svgIcon', [$this, 'svgIcon']),
            new TwigFunction('wizardState', [$this, 'wizardState']),
            new TwigFunction('wizardUrl', [$this, 'wizardUrl']),
        ];
    }

    public function svgIcon(string $icon)
    {
        if (basename($icon) !== $icon) {
            throw new RuntimeException('Icon name must not contain path elements');
        }

        $path = "{$this->iconsDir}/$icon";
        return file_exists($path) ? file_get_contents($path) : '';
    }

    protected $wizardMapping = [
        'pre-enquiry' => ['class' => PreEnquiryState::class, 'route' => InternationalPreEnquiryController::WIZARD_ROUTE],
    ];

    protected function getWizardMeta(string $wizard): array {
        if (!isset($this->wizardMapping[$wizard])) {
            throw new RuntimeException('Unknown wizard');
        }

        return $this->wizardMapping[$wizard];
    }

    public function wizardState(string $wizard, string $state) {
        $class = $this->getWizardMeta($wizard)['class'];
        return constant("$class::$state");
    }

    public function wizardUrl(string $wizard, string $state) {
        $route = $this->getWizardMeta($wizard)['route'];
        $params = ['state' => $this->wizardState($wizard, $state)];

        return $this->router->generate($route, $params);
    }
}