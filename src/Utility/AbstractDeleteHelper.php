<?php

namespace App\Utility;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractDeleteHelper
{
    public function __construct(protected EntityManagerInterface $entityManager, protected TranslatorInterface $translator)
    {
    }
}