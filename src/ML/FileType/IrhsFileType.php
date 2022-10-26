<?php

namespace App\ML\FileType;

class IrhsFileType implements FileTypeInterface
{
    public function getColumnsNames(): array
    {
        return [
            'FirstDate',
            'SecondDate',
            'TotalRoundTripDistance_Std',
            'TotalMileage',
            'BusinessType',
            'TypeOfGoods',      # Textual description of the goods
            'TypeOfGoods_Code', # Target field
            'TypeOfGoods_Std',  # Textual representation of the NST2007 code
            'MOA_Std',
            'DangerousGoods_Std',
            'WeightOfGoodsCarriedKG',
        ];
    }

    public function getName(): string
    {
        return 'irhs';
    }
}