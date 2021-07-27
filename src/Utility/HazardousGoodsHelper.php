<?php

namespace App\Utility;

use App\Entity\HazardousGoods;
use Symfony\Contracts\Translation\TranslatorInterface;

class HazardousGoodsHelper
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getFormChoicesAndOptions(bool $useGroups = false, bool $prefixLabel = true, bool $useHtml = true, bool $includeNotHazardousOption = true): array
    {
        $choiceOptions = [];
        $choices = [];

        $hazardousChoices = HazardousGoods::CHOICES;
        if (!$includeNotHazardousOption) {
            unset($hazardousChoices[HazardousGoods::CODE_PREFIX . HazardousGoods::CODE_0_NOT_HAZARDOUS]);
        }

        foreach ($hazardousChoices as $groupName => $group) {
            $groupLabel = $this->translator->trans($groupName);

            $hasChildren = is_array($group);

            if ($useGroups || !$hasChildren) {
                $choices[$groupLabel] = $hasChildren ? [] : $group;
            }

            if ($hasChildren) {
                foreach ($group as $name => $id) {
                    $labelTranslation = $this->translator->trans($name);
                    if ($prefixLabel) {
                        $labelPrefix = $useHtml ? "<b>{$id}</b> " : "{$id} - ";

                        // Used escape rules from:
                        // vendor/twig/twig/src/Extension/EscaperExtension.php : 232
                        $safeLabel = htmlspecialchars(
                            $labelTranslation,
                            ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                        $label = $labelPrefix . $safeLabel;
                    } else {
                        $label = $labelTranslation;
                    }

                    if ($useGroups) {
                        $choices[$groupLabel][$label] = $id;
                    } else {
                        $choices[$label] = $id;
                        $choiceOptions[$label] = ['label_html' => true];
                    }
                }
            }
        }

        return [$choices, $choiceOptions];
    }
}