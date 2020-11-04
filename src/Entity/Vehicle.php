<?php

namespace App\Entity;

abstract class Vehicle
{
    const AXLE_CONFIGURATIONS = [
        'vehicle.axle.rigid.2.0' => 120,
        'vehicle.axle.rigid.3.0' => 130,
        'vehicle.axle.rigid.4.0' => 140,
        'vehicle.axle.rigid.2.1' => 221,
        'vehicle.axle.rigid.2.2' => 222,
        'vehicle.axle.rigid.2.3' => 223,
        'vehicle.axle.rigid.3.2' => 232,
        'vehicle.axle.rigid.3.3' => 233,
        'vehicle.axle.rigid.other' => 199,
        'vehicle.axle.rigid.other-trailer' => 299,

        'vehicle.axle.articulated.2.1' => 321,
        'vehicle.axle.articulated.2.2' => 322,
        'vehicle.axle.articulated.2.3' => 323,
        'vehicle.axle.articulated.3.2' => 332,
        'vehicle.axle.articulated.3.3' => 333,
        'vehicle.axle.articulated.other' => 399,
    ];

    const ARTICULATED_TRAILER_CONFIGURATIONS = [
        'vehicle.trailer.flat-drop' => 'flat-drop',
        'vehicle.trailer.box' => 'box',
        'vehicle.trailer.temperature-controlled' => 'temperature-controlled',
        'vehicle.trailer.curtain-sided' => 'curtain-sided',
        'vehicle.trailer.liquid' => 'liquid',
        'vehicle.trailer.solid-bulk' => 'solid-bulk',
        'vehicle.trailer.livestock' => 'livestock',
        'vehicle.trailer.car' => 'car',
        'vehicle.trailer.tipper' => 'tipper',
        'vehicle.trailer.other' => 'other',
    ];
}