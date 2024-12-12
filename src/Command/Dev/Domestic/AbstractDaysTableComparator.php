<?php

namespace App\Command\Dev\Domestic;

use App\Command\Dev\AbstractTableExportComparator;
use App\Entity\AbstractGoodsDescription;
use App\Entity\Vehicle;
use App\Utility\Quarter\NaturalQuarterHelper;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Intl\Countries;

abstract class AbstractDaysTableComparator extends AbstractTableExportComparator
{
    public function getDatabaseIdColumns(): array
    {
        return ['SurveyID', 'JourneyID'];
    }

    public function checkRow(array $dbRow, array $exportRow): void
    {
        $this->checkStringColumn('QDSurveyDetailsID', $this->getColumn($exportRow, 'QDSurveyDetailsID'), $dbRow['SurveyID']);
        $this->checkStringColumn('Origin', $this->getColumn($exportRow, 'Origin'), $dbRow['Origin'], true);
        $this->checkStringColumn('Destination', $this->getColumn($exportRow, 'Destination'), $dbRow['Destination'], true);

        $normalisedDbTypeOfGoods = $dbRow['TypeOfGoods'] !== AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER
            ? $dbRow['TypeOfGoods']
            : $dbRow['TypeOfGoodsOther'];

        $exportTypeOfGoods = $this->getColumn($exportRow, 'TypeOfGoods');

        $this->checkStringColumn('TypeOfGoods', $exportTypeOfGoods, $normalisedDbTypeOfGoods, true);

        $this->checkDecimalColumn('Empty', $this->getColumn($exportRow, 'Empty'), $dbRow['DistanceTravelledEmpty']);
        $this->checkDecimalColumn('Loaded', $this->getColumn($exportRow, 'Loaded'), $dbRow['DistanceTravelledLoaded']);

        $this->checkStringColumn('MOA', $this->getColumn($exportRow, 'MOA'), $dbRow['MOA']);
        $this->checkStringColumn('WhereBorderCrossed', $this->getColumn($exportRow, 'WhereBorderCrossed'), $dbRow['WhereBorderCrossed']);
        $this->checkStringColumn('DayOfWeek', $this->getColumn($exportRow, 'DayOfWeek'), $dbRow['DayOfWeek']);

        $this->checkStringColumn('DangerousHazardousGoodsID', $this->getColumn($exportRow, 'DangerousHazardousGoodsID'), $dbRow['DangerousGoodsCode']);

        $this->checkBitmapColumnMatches($exportRow, $dbRow['GoodsTransferredFrom'], [
            'port' => 'FromPort',
            'rail' => 'FromRail',
            'air' => 'FromAir',
        ]);

        $this->checkBitmapColumnMatches($exportRow, $dbRow['GoodsTransferredTo'], [
            'port' => 'ToPort',
            'rail' => 'ToRail',
            'air' => 'ToAir',
        ]);
    }
}