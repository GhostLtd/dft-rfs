<?php


namespace Ghost\GovUkFrontendBundle\Model;


class NotificationBanner
{
    public const FLASH_BAG_TYPE = 'notification-banner';
    public const STYLE_SUCCESS = 'success';

    /**
     * @param mixed[] $options
     */
    public function __construct(
        public $title,
        public $heading,
        public $content,
        /**
         * Valid options are:
         *  - type: false | 'success'
         */
        public $options = []
    )
    {
    }
}