<?php

/**
 * Class Head_Block
 */
class Head_Block extends Core_Block
{
    /**
     * Head_Block constructor.
     */
    public function __construct()
    {
        parent::__construct("head");
        $this->setTemplate("shared.head");
    }
}