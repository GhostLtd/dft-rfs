<?php

namespace App\Utility;

use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractDeleteHelper
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }
}
