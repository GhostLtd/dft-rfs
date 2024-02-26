<?php

namespace App\Utility\Reports;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class DateRangeHelper
{
    public function __construct(protected Connection $connection)
    {
    }

    public function getMinAndMaxYearsForAllSurveys(): array
    {
        $sql = <<<EOQ
SELECT MIN(mm.year_min) AS year_min, MAX(mm.year_max) AS year_max
FROM (
    SELECT MIN(YEAR(survey_period_start)) AS year_min, MAX(YEAR(survey_period_start)) AS year_max
      FROM domestic_survey
      UNION ALL
      SELECT MIN(YEAR(survey_period_start)) AS year_min, MAX(YEAR(survey_period_start)) AS year_max
      FROM international_survey
      UNION ALL
      SELECT MIN(YEAR(survey_period_start)) AS year_min, MAX(YEAR(survey_period_start)) AS year_max
      FROM roro_survey
      UNION ALL
      SELECT MIN(YEAR(dispatch_date)) AS year_min, MAX(YEAR(dispatch_date)) AS year_max
      FROM pre_enquiry
) AS mm;
EOQ;

        try {
            $result = $this->connection->executeQuery($sql);

            if ($result->rowCount() > 0) {
                $row = $result->fetchAssociative();
                return [$row['year_min'], $row['year_max']];
            }
        } catch (Exception) {}

        $year = intval((new \DateTime())->format('Y'));
        return [$year, $year];
    }
}