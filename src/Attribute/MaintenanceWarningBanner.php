<?php

namespace App\Attribute;

/**
 * Marks a controller action whose page should display the upcoming-maintenance warning banner.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class MaintenanceWarningBanner
{
}
