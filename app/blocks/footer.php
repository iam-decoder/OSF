<?php

/**
 * Class Footer_Block
 */
class Footer_Block extends Core_Block
{
    /**
     * Footer_Block constructor.
     */
    public function __construct()
    {
        parent::__construct("footer");
        $this->setTemplate("shared.footer");
    }
}