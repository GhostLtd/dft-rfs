<?php

namespace App\Entity;

use App\Repository\InternationalVehicleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InternationalVehicleRepository::class)
 */
class InternationalVehicle
{
    use VehicleTrait;
}
