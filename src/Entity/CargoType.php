<?php


namespace App\Entity;


class CargoType
{
    const CODE_LB_LIQUID_BULK = "LB";
    const CODE_SB_SOLID_BULK = "SB";
    const CODE_LFC_LARGE_FREIGHT_CONTAINERS = "LFC";
    const CODE_OFC_OTHER_FREIGHT_CONTAINERS = "OFC";
    const CODE_PL_PALLETISED_GOODS = "PL";
    const CODE_PS_PRE_SLUNG_GOODS = "PS";
    const CODE_NP_NO_PACKAGING = "NP";
    const CODE_RC_ROLL_CAGES = "RC";
    const CODE_OT_OTHER_CARGO_TYPES = "OT";
//    const CODE_NS_EMPTY = "NS";

    const TRANSLATION_PREFIX = "goods.cargo-type.options.";

    const CHOICES = [
        self::TRANSLATION_PREFIX . self::CODE_LB_LIQUID_BULK => self::CODE_LB_LIQUID_BULK,
        self::TRANSLATION_PREFIX . self::CODE_SB_SOLID_BULK => self::CODE_SB_SOLID_BULK,
        self::TRANSLATION_PREFIX . self::CODE_LFC_LARGE_FREIGHT_CONTAINERS => self::CODE_LFC_LARGE_FREIGHT_CONTAINERS,
        self::TRANSLATION_PREFIX . self::CODE_OFC_OTHER_FREIGHT_CONTAINERS => self::CODE_OFC_OTHER_FREIGHT_CONTAINERS,
        self::TRANSLATION_PREFIX . self::CODE_PL_PALLETISED_GOODS => self::CODE_PL_PALLETISED_GOODS,
        self::TRANSLATION_PREFIX . self::CODE_PS_PRE_SLUNG_GOODS => self::CODE_PS_PRE_SLUNG_GOODS,
        self::TRANSLATION_PREFIX . self::CODE_NP_NO_PACKAGING => self::CODE_NP_NO_PACKAGING,
        self::TRANSLATION_PREFIX . self::CODE_RC_ROLL_CAGES => self::CODE_RC_ROLL_CAGES,
        self::TRANSLATION_PREFIX . self::CODE_OT_OTHER_CARGO_TYPES => self::CODE_OT_OTHER_CARGO_TYPES,
//        self::TRANSLATION_PREFIX . self::CODE_NS_EMPTY => self::CODE_NS_EMPTY,
    ];

    public static function getFormChoicesAndOptions(): array
    {
        $choiceOptions = [];
        foreach (CargoType::CHOICES as $k=>$v) {
            $choiceOptions[$k] = [
                'help' => "goods.cargo-type.help.{$v}",
            ];
        }

        return [CargoType::CHOICES, $choiceOptions];
    }
}