<?php

namespace App\Twig;

use App\Controller\InternationalSurvey\AbstractActionController;
use App\Controller\PreEnquiry\PreEnquiryController;
use App\Controller\InternationalSurvey\ActionController;
use App\Controller\InternationalSurvey\TripEditController;
use App\Controller\InternationalSurvey\VehicleEditController;
use App\Entity\AbstractGoodsDescription;
use App\Entity\Address;
use App\Entity\CountryInterface;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\StopTrait;
use App\Entity\GoodsDescriptionInterface;
use App\Entity\HaulageSurveyInterface;
use App\Entity\Vehicle;
use App\Controller\InternationalSurvey\InitialDetailsController;
use App\Features;
use App\Utility\LoadingPlaceHelper;
use App\Utility\PostcodeHelper;
use App\Utility\RegistrationMarkHelper;
use App\Utility\SessionTimeoutHelper;
use App\Utility\Domestic\WeekNumberHelper;
use App\Workflow\PreEnquiry\PreEnquiryState;
use App\Workflow\InternationalSurvey\ActionState;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use App\Workflow\InternationalSurvey\TripState;
use App\Workflow\InternationalSurvey\VehicleState;
use DateTimeInterface;
use Exception;
use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public const TRANSFER_UNLOADING = 'unloading';
    public const TRANSFER_LOADING = 'loading';

    protected string $iconsDir;

    public function __construct(
        KernelInterface $kernel,
        protected RouterInterface $router,
        protected TranslatorInterface $translator,
        protected Features $features,
        protected LoadingPlaceHelper $loadingPlaceHelper,
        protected SessionTimeoutHelper $sessionTimeoutHelper
    ) {
        $projectDir = $kernel->getProjectDir();
        $this->iconsDir = "$projectDir/assets/icons";
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('formatAddress', $this->formatAddress(...)),
            new TwigFilter('formatBool', fn($bool) => 'common.choices.boolean.' . ($bool ? 'yes' : 'no')),
            new TwigFilter('formatCountry', $this->formatCountry(...)),
            new TwigFilter('formatGoodsDescription', fn($stop, $short = false) =>
                $stop instanceof GoodsDescriptionInterface ?
                    $this->formatGoodsDescription($stop->getGoodsDescription(), $stop->getGoodsDescriptionOther(), $short) :
                    ''
            ),
            new TwigFilter('formatGoodsTransferDetails', $this->formatGoodsTransferDetails(...)),
            new TwigFilter('formatRegMark', $this->formatRegMark(...)),
            new TwigFilter('formatSurveyPeriod', $this->formatSurveyPeriod(...)),
            new TwigFilter('formatPotentialPostcode', fn(?string $a) => PostcodeHelper::formatIfPostcode($a, true)),
            new TwigFilter('vehicleAxleConfigTransKey', Vehicle::getAxleConfigurationTranslationKey(...)),
            new TwigFilter('weekNumberAndYear', fn(?DateTimeInterface $date) => $date ?
                WeekNumberHelper::getYearlyWeekNumberAndYear($date) :
                [null, null]
            ),
            new TwigFilter('removeEmailNamespacePrefix', function($username) {
                preg_match('/^(?:[^:"]+\:)?(?<email>.*)$/', $username, $matches);
                return $matches['email'] ?? 'unknown';
            }),
        ];
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('choiceLabel', $this->choiceLabel(...)),
            new TwigFunction('is_feature_enabled', $this->isFeatureEnabled(...)),
            new TwigFunction('flattenChoices', $this->flattenChoices(...)),
            new TwigFunction('formatGoodsDescription', $this->formatGoodsDescription(...)),
            new TwigFunction('labelForLoadingAction', $this->loadingPlaceHelper->getLabelForLoadingAction(...)),
            new TwigFunction('sessionExpiryTime', $this->sessionTimeoutHelper->getExpiryTime(...)),
            new TwigFunction('sessionWarningTime', $this->sessionTimeoutHelper->getWarningTime(...)),
            new TwigFunction('shiftMapping', $this->shiftMapping(...)),
            new TwigFunction('svgIcon', $this->svgIcon(...), ['is_safe' => ['html']]),
            new TwigFunction('unloadingSummary', $this->loadingPlaceHelper->getUnloadingSummary(...)),
            new TwigFunction('wizardState', $this->wizardState(...)),
            new TwigFunction('wizardUrl', $this->wizardUrl(...)),
        ];
    }

    public function formatGoodsDescription(?string $goodsDescription, ?string $goodsDescriptionOther, bool $short = false): ?string
    {
        return $goodsDescription === AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER
            ? $goodsDescriptionOther
            : ($short ?
                $this->translator->trans("goods.description.short-options.{$goodsDescription}") :
                $this->translator->trans("goods.description.options.{$goodsDescription}"));
    }

    public function isFeatureEnabled($str): bool {
        try {
            return $this->features->isEnabled($str, true);
        } catch(Exception) {
            throw new SyntaxError("Unknown feature '{$str}'");
        }
    }

    public function formatRegMark($regMark): ?string
    {
        return (new RegistrationMarkHelper($regMark))->getFormattedRegistrationMark();
    }

    public function formatAddress($address, bool $addNewlines=false, bool $showLineOne=true): string {
        if (!$address instanceof Address) {
            return '';
        }

        $separator = $addNewlines ? ",\n": ", ";
        return implode($separator, array_filter([
            $showLineOne ? $address->getLine1() : null,
            $address->getLine2(),
            $address->getLine3(),
            $address->getLine4(),
            method_exists($address, 'getLine5') ? $address->getLine5() : null,
            method_exists($address, 'getLine6') ? $address->getLine6() : null,
            $address->getPostcode(),
        ]));
    }

    function formatCountry(CountryInterface $entity): ?string
    {
        $country = $entity->getCountry();
        
        return $country ?
            Countries::getName($country) :
            $entity->getCountryOther();
    }

    function formatGoodsTransferDetails($stop, $loadingOrUnloading, $nonBlankPrefix = ''): string {
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

    public function formatSurveyPeriod(HaulageSurveyInterface $survey)
    {
        $numDays = $survey->getSurveyPeriodInDays();
        $translationId = 'common.survey-period.' . ($numDays === 1 ? 'one-day' : 'many-days');

        $params = [
            'days' => $numDays,
            'start' => $survey->getSurveyPeriodStart(),
        ];

        if ($numDays > 1) {
            $params['end'] = $survey->getSurveyPeriodEnd();
        }

        return $this->translator->trans($translationId, $params);
    }

    public function svgIcon(string $icon): string
    {
        if (basename($icon) !== $icon) {
            throw new RuntimeException('Icon name must not contain path elements');
        }

        $path = "{$this->iconsDir}/$icon";
        return file_exists($path) ? file_get_contents($path) : '';
    }

    protected array $wizardMapping = [
        'pre-enquiry' => ['class' => PreEnquiryState::class, 'route' => PreEnquiryController::WIZARD_ROUTE],
        'international-initial-details' => ['class' => InitialDetailsState::class, 'route' => InitialDetailsController::WIZARD_ROUTE],
        'international-vehicle' => ['class' => VehicleState::class, 'route' => VehicleEditController::WIZARD_ROUTE],
        'international-trip' => ['class' => TripState::class, 'route' => TripEditController::WIZARD_ROUTE],
        'international-action' => ['class' => ActionState::class, 'route' => 'app_internationalsurvey_action_edit_state'],
    ];

    protected function getWizardMeta(string $wizard): array {
        if (!isset($this->wizardMapping[$wizard])) {
            throw new RuntimeException('Unknown wizard');
        }

        return $this->wizardMapping[$wizard];
    }

    public function wizardState(string $wizard, string $state): string {
        $class = $this->getWizardMeta($wizard)['class'];
        return constant("$class::$state");
    }

    public function wizardUrl(string $wizard, string $state, array $params=[]): string {
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

    public function flattenChoices(array $choices): array {
        $output = [];
        foreach($choices as $label => $choice) {
            if (is_array($choice)) {
                $output = array_merge($output, $this->flattenChoices($choice));
            } else {
                $output[$label] = $choice;
            }
        }

        return $output;
    }

    public function shiftMapping(array $mapping, int $key, string $direction): array {
        if (!in_array($direction, ['up', 'down'])) {
            throw new RuntimeException('Direction must be "up" or "down"');
        }

        if ($key < 0 || $key >= count($mapping)) {
            throw new RuntimeException('Key out of bounds');
        }

        if (($key === 0 && $direction === 'up') || ($key === count($mapping) - 1 && $direction === 'down')) {
            return $mapping;
        }

        $temp = $mapping[$key];

        if ($direction === 'up') {
            $mapping[$key] = $mapping[$key - 1];
            $mapping[$key - 1] = $temp;
        } else {
            $mapping[$key] = $mapping[$key + 1];
            $mapping[$key + 1] = $temp;
        }

        return $mapping;
    }
}