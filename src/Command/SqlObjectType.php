<?php

declare(strict_types=1);

namespace App\Command;

enum SqlObjectType: string
{
    case View = 'view';
    case Function = 'function';
}
