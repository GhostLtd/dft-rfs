<?php

namespace App\Entity;

abstract class Vehicle
{
    const OPERATION_TYPE_FOR_HIRE_AND_REWARD = 'for-hire-and-reward';
    const OPERATION_TYPE_ON_OWN_ACCOUNT = 'on-own-account';
    const OPERATION_TYPE_TRANSLATION_PREFIX = 'vehicle.operation-type.';

    const OPERATION_TYPE_CHOICES = [
        self::OPERATION_TYPE_TRANSLATION_PREFIX . self::OPERATION_TYPE_FOR_HIRE_AND_REWARD => self::OPERATION_TYPE_FOR_HIRE_AND_REWARD,
        self::OPERATION_TYPE_TRANSLATION_PREFIX . self::OPERATION_TYPE_ON_OWN_ACCOUNT => self::OPERATION_TYPE_ON_OWN_ACCOUNT,
    ];

    const TRAILER_CONFIGURATION_RIGID = 100;
    const TRAILER_CONFIGURATION_RIGID_TRAILER = 200;
    const TRAILER_CONFIGURATION_ARTICULATED = 300;

    const TRAILER_CONFIGURATION_CHOICES = [
        'vehicle.axle.articulated.label' => self::TRAILER_CONFIGURATION_ARTICULATED,
        'vehicle.axle.rigid.label' => self::TRAILER_CONFIGURATION_RIGID,
        'vehicle.axle.rigid-and-trailer.label' => self::TRAILER_CONFIGURATION_RIGID_TRAILER,
    ];

    const AXLE_CONFIGURATION_CHOICES = [
        self::TRAILER_CONFIGURATION_ARTICULATED => [
            'vehicle.axle.articulated.2.1' => 321,
            'vehicle.axle.articulated.2.2' => 322,
            'vehicle.axle.articulated.2.3' => 323,
            'vehicle.axle.articulated.3.2' => 332,
            'vehicle.axle.articulated.3.3' => 333,
            'vehicle.axle.articulated.other' => 399,
        ],
        self::TRAILER_CONFIGURATION_RIGID => [
            'vehicle.axle.rigid.2.0' => 120,
            'vehicle.axle.rigid.3.0' => 130,
            'vehicle.axle.rigid.4.0' => 140,
            'vehicle.axle.rigid.other' => 199,
        ],
        self::TRAILER_CONFIGURATION_RIGID_TRAILER => [
            'vehicle.axle.rigid-and-trailer.2.1' => 221,
            'vehicle.axle.rigid-and-trailer.2.2' => 222,
            'vehicle.axle.rigid-and-trailer.2.3' => 223,
            'vehicle.axle.rigid-and-trailer.3.2' => 232,
            'vehicle.axle.rigid-and-trailer.3.3' => 233,
            'vehicle.axle.rigid-and-trailer.other' => 299,
        ],
    ];

    const BODY_TYPE_FLAT_DROP = 'flat-drop';
    const BODY_TYPE_BOX = 'box';
    const BODY_TYPE_TEMPERATURE_CONTROLLED = 'temperature-controlled';
    const BODY_TYPE_CURTAIN_SIDED = 'curtain-sided';
    const BODY_TYPE_LIQUID = 'liquid';
    const BODY_TYPE_SOLID_BULK = 'solid-bulk';
    const BODY_TYPE_LIVESTOCK = 'livestock';
    const BODY_TYPE_CAR = 'car';
    const BODY_TYPE_TIPPER = 'tipper';
    const BODY_TYPE_OTHER = 'other';

    const BODY_CONFIGURATION_TRANSLATION_PREFIX = 'vehicle.body.';
    const BODY_CONFIGURATION_CHOICES = [
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . self::BODY_TYPE_BOX => self::BODY_TYPE_BOX,
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . self::BODY_TYPE_CAR => self::BODY_TYPE_CAR,
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . self::BODY_TYPE_CURTAIN_SIDED => self::BODY_TYPE_CURTAIN_SIDED,
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . self::BODY_TYPE_FLAT_DROP => self::BODY_TYPE_FLAT_DROP,
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . self::BODY_TYPE_LIQUID => self::BODY_TYPE_LIQUID,
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . self::BODY_TYPE_LIVESTOCK => self::BODY_TYPE_LIVESTOCK,
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . self::BODY_TYPE_SOLID_BULK => self::BODY_TYPE_SOLID_BULK,
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . self::BODY_TYPE_TEMPERATURE_CONTROLLED => self::BODY_TYPE_TEMPERATURE_CONTROLLED,
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . self::BODY_TYPE_TIPPER => self::BODY_TYPE_TIPPER,
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . self::BODY_TYPE_OTHER => self::BODY_TYPE_OTHER,
    ];

    static function getAxleConfigurationTranslationKey($axleConfigCode)
    {
        if (!$axleConfigCode) return null;
        return array_flip(self::AXLE_CONFIGURATION_CHOICES[100 * floor($axleConfigCode / 100)])[$axleConfigCode];
    }
}