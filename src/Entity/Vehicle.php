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

    const TRAILER_CONFIGURATION_CHOICES = [
        'vehicle.axle.rigid.label' => 100,
        'vehicle.axle.rigid-and-trailer.label' => 200,
        'vehicle.axle.articulated.label' => 300,
    ];

    const AXLE_CONFIGURATION_CHOICES = [
        100 => [
            'vehicle.axle.rigid.2.0' => 120,
            'vehicle.axle.rigid.3.0' => 130,
            'vehicle.axle.rigid.4.0' => 140,
            'vehicle.axle.rigid.other' => 199,
        ],
        200 => [
            'vehicle.axle.rigid-and-trailer.2.1' => 221,
            'vehicle.axle.rigid-and-trailer.2.2' => 222,
            'vehicle.axle.rigid-and-trailer.2.3' => 223,
            'vehicle.axle.rigid-and-trailer.3.2' => 232,
            'vehicle.axle.rigid-and-trailer.3.3' => 233,
            'vehicle.axle.rigid-and-trailer.other' => 299,
        ],
        300 => [
            'vehicle.axle.articulated.2.1' => 321,
            'vehicle.axle.articulated.2.2' => 322,
            'vehicle.axle.articulated.2.3' => 323,
            'vehicle.axle.articulated.3.2' => 332,
            'vehicle.axle.articulated.3.3' => 333,
            'vehicle.axle.articulated.other' => 399,
        ],
    ];

    const BODY_CONFIGURATION_TRANSLATION_PREFIX = 'vehicle.body.';
    const BODY_CONFIGURATION_CHOICES = [
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . 'flat-drop' => 'flat-drop',
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . 'box' => 'box',
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . 'temperature-controlled' => 'temperature-controlled',
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . 'curtain-sided' => 'curtain-sided',
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . 'liquid' => 'liquid',
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . 'solid-bulk' => 'solid-bulk',
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . 'livestock' => 'livestock',
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . 'car' => 'car',
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . 'tipper' => 'tipper',
        self::BODY_CONFIGURATION_TRANSLATION_PREFIX . 'other' => 'other',
    ];
}