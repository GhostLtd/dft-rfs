<?php


namespace App\Entity;

interface BlameLoggable
{
    public function getId();

    /**
     * return string
     */
    public function getBlameLogLabel();

    public function getAssociatedEntityClass();
    public function getAssociatedEntityId();

}
