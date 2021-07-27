<?php


namespace Ghost\GovUkFrontendBundle\Model;


class NotificationBanner
{
    const FLASH_BAG_TYPE = 'notification-banner';
    const STYLE_SUCCESS = 'success';

    public $title;
    public $heading;
    public $content;

    /**
     * Valid options are:
     *  - type: false | 'success'
     * @var array
     */
    public $options;

    public function __construct($title, $heading, $content, $options = [])
    {
        $this->title = $title;
        $this->heading = $heading;
        $this->content = $content;
        $this->options = $options;
    }
}