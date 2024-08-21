<?php

namespace App\ML\Config;

use App\ML\Stats;
use App\ML\ValidationException;

class DefaultTabularConfig extends AbstractDefaultConfig
{
    #[\Override]
    public function getName(): string
    {
        return 'default-tabular';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Business type, MOA, Dangerous goods, Type of goods -> Type of goods code';
    }

    #[\Override]
    public function getFiletypesAndColumnsConfig(): array
    {
        return [
            'csrgt' => [
                'BusinessType' => [],
                'TypeOfGoods' => [],                                        # Textual description of the goods
                'TypeOfGoods_NST2007' => ['rename' => 'TypeOfGoods_Code'],  # NST2007 code - target field
                'MOA_Std' => ['rename' => 'MOA'],
                'DangerousGoods' => [],
                'WeightOfGoodsCarriedKG' => [],
                'NumberOfStopsForDelivery' => [],
                'NumberOfStopsForCollection' => [],
                'NumberOfStopsForBoth' => [],
            ],
            'irhs' => [
                'BusinessType' => [],
                'TypeOfGoods' => [],                                        # Textual description of the goods
                'TypeOfGoods_Code' => [],                                   # NST2007 code - target field
                'MOA_Std' => ['rename' => 'MOA'],
                'DangerousGoods_Std' => ['rename' => 'DangerousGoods'],
                'WeightOfGoodsCarriedKG' => [],
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
        $businessTypeColumn = 'BusinessType';

        foreach(['NumberOfStopsForDelivery', 'NumberOfStopsForCollection', 'NumberOfStopsForBoth'] as $key) {
            // 'NULL' will be strings from CSRGT files with no values in these columns
            // null will be nulls from IRHS files
            if ($row[$key] === 'NULL' || $row[$key] === null) {
                $row[$key] = '';
            }
        }

        if (trim($row[$businessTypeColumn]) === '') {
            throw new ValidationException(Stats::INVALID_TEXT);
        }

        $row[$businessTypeColumn] = strtoupper($row[$businessTypeColumn]);
        $row[$textColumn] = strtoupper($row[$textColumn]);
        $row[$codeColumn] = $this->normalizeCode($row[$codeColumn]);

        $businessType = $row[$businessTypeColumn];
        $code = $row[$codeColumn];
        $moa = $row['MOA'];
        $text = $row[$textColumn];

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

        if ($text === 'AGGREGATES' && $code === '18.0') {
            // Aggregates are not groupage!
            $row[$codeColumn] = '3.5'; // Stone, sand, ...
        }

        if (in_array($text, ['AIRFREIGHT', 'AIR FREIGHT']) && in_array($code, ['19.1', '19.2'])) {
            // Groupage rather than "unidentifiable goods"
            $row[$codeColumn] = '18.0';
        }

        if ($text === 'BALLAST' && $code === '18.0') {
            // Stone, sand etc rather than groupage
            $row[$codeColumn] = '3.5';
        }

        if ($text === 'BEER' && $code === '18.0') {
            // Beverages rather than groupage
            $row[$codeColumn] = '4.7';
        }

        if (in_array($text, ['BREAD', 'BAKERY PRODUCTS'])  && $code === '4.8') {
            // If it's bread then it's "various food products" (4.9) and not "other food products" (4.8)
            $row[$codeColumn] = '4.9';
        }

        if ($text === 'BLOCKS' && in_array($code, ['9.2', '18.0'])) {
            // Neither cement nor groupage
            $row[$codeColumn] = '9.3'; // Other construction materials
        }

        if ($text === 'BUILDING PRODUCTS' && $code === '18.0') {
            // Not groupage
            $row[$codeColumn] = '9.3'; // Other construction materials
        }

        if ($text === 'DUST' && $code === '19.2') {
            // Stone, sand etc rather than "unidentifiable goods"
            $row[$codeColumn] = '3.5';
        }

        if (in_array($text, [
            'EMPTY CONTAINER',
            'EMPTY LFC',
        ]) && $code === '18.0') {
            $row[$codeColumn] = '16.1'; // Pallets and other packaging in service
        }


        if ($text === 'FOOD' && in_array($code, ['4.7', '18.0'])) {
            // Food, Beverages -> Food (no user would write "FOOD" if they were carrying beverages or groupage)
            $row[$codeColumn] = '4.9';
        }

        if ($businessType === 'FUEL DISTRIBUTION' && $text === 'FUEL' && $moa === 'LB') {
            // If it's fuel being distributed as liquid bulk, then it's definitely 7.2 (Liquid) rather than 7.1 (Gaseous)
            $row[$codeColumn] = '7.2';
        }

        if ($text === 'FURNITURE' && $code === '18.0') {
            // It's furniture and not groupage...
            $row[$codeColumn] = '13.1';
        }

        if ($text === 'COCA COLA') {
            // This is a beverage, and not a "grain product"
            $row[$codeColumn] = '4.7';
        }

        if (in_array($text, [
            'GOODS',
            'GROUPAGE',
            'MIXED PALLETS',
            'PALLETISED GOODS',
            'PALLET GOODS',
            'VARIOUS PRODUCTS'
        ])) {
            $row[$codeColumn] = '18.0'; // Groupage is indeed groupage
        }

        if ($text === 'HOUSEHOLD GOODS' && in_array($code, ['11.2', '13.2'])) {
            // Groupage (18.0) rather than "white goods" (11.2) or "other manufactured goods" (13.2)
            $row[$codeColumn] = '18.0';
        }

        if ($text == 'PLASTERBOARD' && $code === '9.2') {
            // Not cement (9.2)!
            $row[$codeColumn] = '9.3'; // Other construction materials
        }

        if (in_array($businessType, [
            'FURNITURE REMOVALS',
            'HOUSEHOLD REMOVALS',
            'REMOVALS',
            'REMOVALS AND STORAGE',
        ])) {
            if (in_array($text, [
                'FURNITURE',
                'HOUSEHOLD FURNITURE',
                'HOUSEHOLD GOODS',
            ])) {
                // If the business is a removals business, then this is 17.1 and not groupage or furniture or
                // domestic appliances!
                $row[$codeColumn] = '17.1'; // Household removal
            }
        }

        if ($text === 'SAND' && $code === '18.0') {
            // Not groupage!
            $row[$codeColumn] = '3.5'; // Stone, sand, ...
        }

        if ($text === 'STEEL' && $code === '11.2') {
            // If someone's written "STEEL", then they're certainly not transporting white goods
            $row[$codeColumn] = '10.1'; // Basic iron and steel ...
        }

        if ($text === 'TIMBER') {
            if ($businessType === 'CONSTRUCTION') {
                // If it's for construction then it's a processed product (e.g. trusses)
                $row[$codeColumn] = '6.1'; // Products of wood and cork (except furniture)
            }

            if ($businessType === 'HAULAGE') {
                if ($moa === 'PL' || $moa === 'PS') {
                    // If it's on pallets on is pre-slung, then it must be processed wood rather than raw materials
                    $row[$codeColumn] = '6.1'; // Products of wood and cork (except furniture)
                } else {
                    // Timber is either 6.1 or 1.5 depending upon whether it's raw materials or processed, but it's
                    // certainly not groupage (18.0)
                    if ($code === '18.0') {
                        throw new ValidationException(STATS::EXCLUDED);
                    }
                }
            }

            if (in_array($businessType, [
                'BUILDERS MERCHANT',
                'TIMBER MERCHANT',
                'WHOLESALE',
            ])) {
                // Merchants sell processed wood (e.g. planks)
                $row[$codeColumn] = '6.1';
            }
        }

        return $row;
    }

    #[\Override]
    public function shouldOutputHeader(): bool
    {
        return false;
    }
}