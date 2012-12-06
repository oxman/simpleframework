<?php

namespace simpleframework;

class Autoloader
{


    static public function register()
    {

        spl_autoload_register('self::autoload');

    }


    static public function autoload($class)
    {

        if (file_exists($file = ROOT . '/app/model/' . $class . '.php') === true) {
            include_once $file;
        }

    }


}