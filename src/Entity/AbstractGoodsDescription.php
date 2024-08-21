<?php


namespace App\Entity;


abstract class AbstractGoodsDescription
{
    public const GOODS_DESCRIPTION_PACKAGING = 'packaging';
    public const GOODS_DESCRIPTION_GROUPAGE = 'groupage';
    public const GOODS_DESCRIPTION_EMPTY = 'empty';
    public const GOODS_DESCRIPTION_OTHER = 'other-goods';

    public const GOODS_DESCRIPTION_TRANSLATION_PREFIX = 'goods.description.options.';
    public const GOODS_DESCRIPTION_CHOICES = [
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_GROUPAGE => self::GOODS_DESCRIPTION_GROUPAGE,
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_PACKAGING => self::GOODS_DESCRIPTION_PACKAGING,
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_EMPTY => self::GOODS_DESCRIPTION_EMPTY,
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_OTHER => self::GOODS_DESCRIPTION_OTHER,
    ];

    public static function getFormChoicesAndOptions(bool $excludeEmpty)
    {
        $choiceOptions = [];
        foreach (self::GOODS_DESCRIPTION_CHOICES as $k=>$v) {
            $choiceOptions[$k] = [
                'help' => "goods.description.help.{$v}",
            ];
        }

        $choiceOptions[self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_OTHER]['conditional_form_name'] = 'goodsDescriptionOther';

        $choices = AbstractGoodsDescription::GOODS_DESCRIPTION_CHOICES;
        if ($excludeEmpty) {
            unset($choices[array_search(AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY, $choices)]);
        }

        return [$choices, $choiceOptions];
    }
}