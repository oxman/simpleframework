<?php

namespace simpleframework;

class Controller
{

    public $view     = array();
    public $helper   = null;
    public $viewName = null;


    public function getConfig($key, $subkey=null)
    {

        return Kernel::getConfig($key, $subkey);

    }


    public function getCurrentRoute()
    {

        return Kernel::getCurrentRoute();

    }


    public function getRoute($key, $params=array())
    {

        return Kernel::getRoute($key, $params);

    }


    public function getFullRoute($domain, $key, $params=array())
    {

        return Kernel::getFullRoute($domain, $key, $params);

    }


    public function pre($params)
    {

    }


    public function post($params)
    {

    }


}