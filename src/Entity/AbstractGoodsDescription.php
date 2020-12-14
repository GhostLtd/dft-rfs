<?php


namespace App\Entity;


class AbstractGoodsDescription
{
    const GOODS_DESCRIPTION_EMPTY_CONTAINER = 'empty-container';
    const GOODS_DESCRIPTION_PACKAGING = 'packaging';
    const GOODS_DESCRIPTION_GROUPAGE = 'groupage';
    const GOODS_DESCRIPTION_EMPTY = 'empty';
    const GOODS_DESCRIPTION_OTHER = 'other-goods';

    const GOODS_DESCRIPTION_TRANSLATION_PREFIX = 'goods.description.options.';
    const GOODS_DESCRIPTION_CHOICES = [
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_EMPTY_CONTAINER => self::GOODS_DESCRIPTION_EMPTY_CONTAINER,
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_PACKAGING => self::GOODS_DESCRIPTION_PACKAGING,
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_GROUPAGE => self::GOODS_DESCRIPTION_GROUPAGE,
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_EMPTY => self::GOODS_DESCRIPTION_EMPTY,
        self::GOODS_DESCRIPTION_TRANSLATION_PREFIX . self::GOODS_DESCRIPTION_OTHER => self::GOODS_DESCRIPTION_OTHER,
    ];
}