<?php

namespace App\Twig;

use App\Controller\InternationalPreEnquiry\PreEnquiryController;
use App\Controller\InternationalSurvey\TripEditController;
use App\Controller\InternationalSurvey\VehicleEditController;
use App\Entity\AbstractGoodsDescription;
use App\Entity\Address;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\StopTrait;
use App\Entity\ValueUnitInterface;
use App\Entity\Vehicle;
use App\Controller\InternationalSurvey\InitialDetailsController;
use App\Utility\RegistrationMarkHelper;
use App\Workflow\InternationalPreEnquiry\PreEnquiryState;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use App\Workflow\InternationalSurvey\TripState;
use App\Workflow\InternationalSurvey\VehicleState;
use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    const TRANSFER_UNLOADING = 'unloading';
    const TRANSFER_LOADING = 'loading';
    protected $iconsDir;

    protected $router;

    protected $translator;

    public function __construct(KernelInterface $kernel, RouterInterface $router, TranslatorInterface $translator) {
        $projectDir = $kernel->getProjectDir();
        $this->iconsDir = "$projectDir/assets/icons";
        $this->router = $router;
        $this->translator = $translator;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('vehicleAxleConfigTransKey', [Vehicle::class, 'getAxleConfigurationTranslationKey']),
            new TwigFilter('formatRegMark', [$this, 'formatRegMark']),
            new TwigFilter('formatBool', function($bool){return 'common.choices.boolean.' . ($bool ? 'yes' : 'no');}),
            new TwigFilter('formatAddress', [$this, 'formatAddress']),
            new TwigFilter('formatBool', function($bool){return 'common.choices.boolean.' . ($bool ? 'yes' : 'no');}),
            new TwigFilter('formatValueUnit', function (ValueUnitInterface $a){return "{$a->getValue()} {$a->getUnit()}";}),
            new TwigFilter('formatGoodsDescription', function($stop, $short = false){
                if (!in_array(StopTrait::class, class_uses($stop))) {
                    return '';
                }
                return ($stop->getGoodsDescription() === AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER
                    ? $stop->getGoodsDescriptionOther()
                    : ($short ?
                        $stop->getGoodsDescription() :
                        $this->translator->trans("goods.description.options.{$stop->getGoodsDescription()}")
                    ));
            }),
            new TwigFilter('formatGoodsTransferDetails', [$this, 'formatGoodsTransferDetails']),
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

    public function formatAddress($address, bool $addNewlines=false): string {
        if (!$address instanceof Address) {
            return '';
        }

        $separator = $addNewlines ? ",\n": ", ";
        return implode($separator, array_filter([$address->getLine1(), $address->getLine2(), $address->getLine3(), $address->getLine4(), $address->getPostcode()]));
    }

    function formatGoodsTransferDetails($stop, $loadingOrUnloading, $nonBlankPrefix = '') {
        if (!in_array(StopTrait::class, class_uses($stop)) || !in_array($loadingOrUnloading, [self::TRANSFER_LOADING, self::TRANSFER_UNLOADING])) {
            return '';
        }

        $isLoadingMode = $loadingOrUnloading === self::TRANSFER_LOADING;
        $transferredToOrFrom = $isLoadingMode ? $stop->getGoodsTransferredFrom() : $stop->getGoodsTransferredTo();

        $parts = [];

        if ($isLoadingMode && $stop->getGoodsLoaded()) {
            $parts[] = "loaded";
        } else if (!$isLoadingMode && $stop->getGoodsUnloaded()) {
            $parts[] = "unloaded";
        } else {
            return '';
        }

        if ($transferredToOrFrom === Day::TRANSFERRED_PORT) {
            $parts[] = "docks";
        } else if ($transferredToOrFrom === Day::TRANSFERRED_RAIL) {
            $parts[] = "rail";
        } else if ($transferredToOrFrom === Day::TRANSFERRED_AIR) {
            $parts[] = "airport";
        } else {
            $parts[] = "none";
        }

        return $nonBlankPrefix . $this->translator->trans("domestic.day-view." . join('.', $parts));
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
        'international-trip' => ['class' => TripState::class, 'route' => TripEditController::WIZARD_ROUTE],
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