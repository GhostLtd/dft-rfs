<?php


namespace App\Entity;


class HazardousGoods
{
    const CODE_0_NOT_HAZARDOUS = "0";

    const CODE_1_EXPLOSIVE = "1";

    const CODE_2_1_FLAMMABLE_GAS = "2.1";
    const CODE_2_2_NON_FLAMMABLE = "2.2";
    const CODE_2_3_TOXIC_GAS = "2.3";

    const CODE_3_FLAMMABLE_LIQUID = "3";

    const CODE_4_1_FLAMMABLE_SOLID = "4.1";
    const CODE_4_2_SPONTANEOUSLY_COMBUSTIBLE_SUBSTANCE = "4.2";
    const CODE_4_3_SUBSTANCE_CONTACT_WATER = "4.3";

    const CODE_5_1_OXIDISING_SUBSTANCE = "5.1";
    const CODE_5_2_ORGANIC_PEROXIDE = "5.2";

    const CODE_6_1_TOXIC_SUBSTANCE = "6.1";
    const CODE_6_2_INFECTIOUS_SUBSTANCE = "6.2";

    const CODE_7_RADIOACTIVE_MATERIAL = "7";

    const CODE_8_CORROSIVE_SUBSTANCES = "8";

    const CODE_9_MISCELLANEOUS = "9";

    const CODE_PREFIX = 'goods.hazardous.';
    const GROUP_PREFIX = 'goods.hazardous.group.';

    const GROUP_1_EXPLOSIVE = "1";
    const GROUP_2_GASES = "2";
    const GROUP_3_FLAMMABLE_LIQUIDS = "3";
    const GROUP_4_FLAMMABLE_SOLIDS = "4";
    const GROUP_5_OXIDISING_SUBSTANCES = "5";
    const GROUP_6_TOXIC_SUBSTANCES = "6";
    const GROUP_7_RADIOACTIVE_MATERIAL = "7";
    const GROUP_8_CORROSIVE_SUBSTANCES = "8";
    const GROUP_9_MISCELLANEOUS = "9";

    const CHOICES = [
        self::CODE_PREFIX . self::CODE_0_NOT_HAZARDOUS => self::CODE_0_NOT_HAZARDOUS,
        self::CODE_PREFIX . self::GROUP_1_EXPLOSIVE => [
            self::CODE_PREFIX . self::CODE_1_EXPLOSIVE => self::CODE_1_EXPLOSIVE,
        ],
        self::GROUP_PREFIX . self::GROUP_2_GASES => [
            self::CODE_PREFIX . self::CODE_2_1_FLAMMABLE_GAS => self::CODE_2_1_FLAMMABLE_GAS,
            self::CODE_PREFIX . self::CODE_2_2_NON_FLAMMABLE => self::CODE_2_2_NON_FLAMMABLE,
            self::CODE_PREFIX . self::CODE_2_3_TOXIC_GAS => self::CODE_2_3_TOXIC_GAS,
        ],
        self::GROUP_PREFIX . self::GROUP_3_FLAMMABLE_LIQUIDS => [
            self::CODE_PREFIX . self::CODE_3_FLAMMABLE_LIQUID => self::CODE_3_FLAMMABLE_LIQUID,
        ],
        self::GROUP_PREFIX . self::GROUP_4_FLAMMABLE_SOLIDS => [
            self::CODE_PREFIX . self::CODE_4_1_FLAMMABLE_SOLID => self::CODE_4_1_FLAMMABLE_SOLID,
            self::CODE_PREFIX . self::CODE_4_2_SPONTANEOUSLY_COMBUSTIBLE_SUBSTANCE => self::CODE_4_2_SPONTANEOUSLY_COMBUSTIBLE_SUBSTANCE,
            self::CODE_PREFIX . self::CODE_4_3_SUBSTANCE_CONTACT_WATER => self::CODE_4_3_SUBSTANCE_CONTACT_WATER,
        ],
        self::GROUP_PREFIX . self::GROUP_5_OXIDISING_SUBSTANCES => [
            self::CODE_PREFIX . self::CODE_5_1_OXIDISING_SUBSTANCE => self::CODE_5_1_OXIDISING_SUBSTANCE,
            self::CODE_PREFIX . self::CODE_5_2_ORGANIC_PEROXIDE => self::CODE_5_2_ORGANIC_PEROXIDE,
        ],
        self::GROUP_PREFIX . self::GROUP_6_TOXIC_SUBSTANCES => [
            self::CODE_PREFIX . self::CODE_6_1_TOXIC_SUBSTANCE => self::CODE_6_1_TOXIC_SUBSTANCE,
            self::CODE_PREFIX . self::CODE_6_2_INFECTIOUS_SUBSTANCE => self::CODE_6_2_INFECTIOUS_SUBSTANCE,
        ],
        self::GROUP_PREFIX . self::GROUP_7_RADIOACTIVE_MATERIAL => [
            self::CODE_PREFIX . self::CODE_7_RADIOACTIVE_MATERIAL => self::CODE_7_RADIOACTIVE_MATERIAL,
        ],
        self::GROUP_PREFIX . self::GROUP_8_CORROSIVE_SUBSTANCES => [
            self::CODE_PREFIX . self::CODE_8_CORROSIVE_SUBSTANCES => self::CODE_8_CORROSIVE_SUBSTANCES,
        ],
        self::GROUP_PREFIX . self::GROUP_9_MISCELLANEOUS => [
            self::CODE_PREFIX . self::CODE_9_MISCELLANEOUS => self::CODE_9_MISCELLANEOUS,
        ],
    ];
}