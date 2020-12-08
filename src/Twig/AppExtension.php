<?php

namespace App\Twig;

use App\Controller\InternationalPreEnquiry\PreEnquiryController;
use App\Controller\InternationalSurvey\VehicleEditController;
use App\Entity\Vehicle;
use App\Controller\InternationalSurvey\InitialDetailsController;
use App\Utility\RegistrationMarkHelper;
use App\Workflow\InternationalPreEnquiry\PreEnquiryState;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use App\Workflow\InternationalSurvey\VehicleState;
use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
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

    public function getFilters()
    {
        return [
            new TwigFilter('vehicleAxleConfigTransKey', [Vehicle::class, 'getAxleConfigurationTranslationKey']),
            new TwigFilter('formatRegMark', [$this, 'formatRegMark']),
            new TwigFilter('formatBool', function($bool){return 'common.choices.boolean.' . ($bool ? 'yes' : 'no');})
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('svgIcon', [$this, 'svgIcon'], ['is_safe' => ['html']]),
            new TwigFunction('wizardState', [$this, 'wizardState']),
            new TwigFunction('wizardUrl', [$this, 'wizardUrl']),
            new TwigFunction('choiceLabel', [$this, 'choiceLabel']),
        ];
    }

    public function formatRegMark($regMark)
    {
        return (new RegistrationMarkHelper($regMark))->getFormattedRegistrationMark();
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
        'pre-enquiry' => ['class' => PreEnquiryState::class, 'route' => PreEnquiryController::WIZARD_ROUTE],
        'international-initial-details' => ['class' => InitialDetailsState::class, 'route' => InitialDetailsController::WIZARD_ROUTE],
        'international-vehicle' => ['class' => VehicleState::class, 'route' => VehicleEditController::WIZARD_ROUTE],
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

    public function wizardUrl(string $wizard, string $state, array $params=[]) {
        $route = $this->getWizardMeta($wizard)['route'];
        $stateParams = ['state' => $this->wizardState($wizard, $state)];

        return $this->router->generate($route, array_merge($params, $stateParams));
    }

    public function choiceLabel(array $choices, ?string $choice, bool $equivalence=false): string {
        foreach($choices as $label => $value) {
            if ($value === $choice || ($equivalence && $value == $choice)) {
                return $label;
            }
        }

        return '';
    }
}