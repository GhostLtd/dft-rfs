<?php

namespace App\Utility\QualityCheck;

enum SubjectType: string
{
    case RESPONSE = 'response';
    case DAY = 'day';
    case STOP = 'stop';
}
