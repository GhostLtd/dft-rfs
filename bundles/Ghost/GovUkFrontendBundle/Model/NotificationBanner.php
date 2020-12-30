<?php


namespace Ghost\GovUkFrontendBundle\Model;


class NotificationBanner
{
    public $title;
    public $heading;
    public $content;

    /**
     * Valid options are:
     *  - type: false | 'success'
     * @var array
     */
    public $options;

    public function __construct($title, $heading, $content, $options = ['type' => false])
    {
        $this->title = $title;
        $this->heading = $heading;
        $this->content = $content;
        $this->options = $options;
    }
}