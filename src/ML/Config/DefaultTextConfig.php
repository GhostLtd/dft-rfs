<?php

namespace App\ML\Config;

use App\ML\Stats;
use App\ML\ValidationException;

class DefaultTextConfig extends DefaultTabularConfig
{
    #[\Override]
    public function getName(): string
    {
        return 'default-text';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Type of goods -> Type of goods code';
    }

    #[\Override]
    public function shouldOutputHeader(): bool
    {
        return false;
    }

    #[\Override]
    public function getFiletypesAndColumnsConfig(): array
    {
        return [
            'csrgt' => [
                'TypeOfGoods' => [],                                        # Textual description of the goods
                'TypeOfGoods_NST2007' => ['rename' => 'TypeOfGoods_Code'],  # NST2007 code - target field
            ],
            'irhs' => [
                'TypeOfGoods' => [],                                        # Textual description of the goods
                'TypeOfGoods_Code' => [],                                   # NST2007 code - target field
            ],
        ];
    }

    #[\Override]
    public function getTargetColumnName(): string {
        return "TypeOfGoods_Code";
    }

    #[\Override]
    public function normalizeAndErrorCheck(array $row): array {
        $codeColumn = 'TypeOfGoods_Code';
        $textColumn = 'TypeOfGoods';

        $row[$textColumn] = strtoupper($row[$textColumn]);
        $row[$codeColumn] = $this->normalizeCode($row[$codeColumn]);

        $text = $row[$textColumn];
        $code = $row[$codeColumn];

        // Blacklist
        if (in_array($text, [
            // Non-inputs
            '',
            '0',
            'NULL',

            // Empty lorries don't submit a goods description (and also the input data has 97% of EMPTY -> Groupage, which makes no sense)
            'EMPTY',
        ])) {
            throw new ValidationException(Stats::EXCLUDED);
        }

        if ($code === '18.0') {
            // Some erroneously marked as 18.0 - Groupage

            if ($code === 'FOOD') {
                $code = '4.9'; // Various food products ... in parcel service or grouped
            }

            if ($code === 'BEER') {
                $code = '4.7'; // Beverages
            }

            if ($code === 'EMPTY LFC' || $row[$textColumn] === 'EMPTY CONTAINER') {
                $code = '16.1'; // Pallets and other packaging in service
            }
        }

        // Re-order columns as per expected test CSV format (i.e. source,target)
        return [
            $textColumn => $text,
            $codeColumn => $code,
        ];
    }
}