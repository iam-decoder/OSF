<?php

/**
 * Class Header_Block
 */
class Header_Block extends Core_Block
{
    /**
     * Header_Block constructor.
     */
    public function __construct()
    {
        parent::__construct("header");
        $this->setTemplate("shared.header");
    }
}