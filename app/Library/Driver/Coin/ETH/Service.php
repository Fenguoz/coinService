<?php

namespace Driver\Coin\ETH;

use Driver\Coin\AbstractService;

class Service extends AbstractService
{
    protected $key;
    protected $url;
    protected $phone_number;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function address()
    {
        echo 1;
        return $this->_notify();
    }

    public function _return()
    {
    }
}
