<?php

namespace simpleframework;

require_once ROOT . '/vendor/Twig/LoaderInterface.php';

class LoaderTemplate implements \Twig_LoaderInterface
{

    public $path;


    public function __construct($path)
    {

        $this->path = $path;

    }


    public function getSource($name)
    {

        return file_get_contents($this->_findTemplate($name));

    }


    public function getCacheKey($name)
    {

        return $name;

    }


    public function isFresh($name, $time)
    {

        return filemtime($this->_findTemplate($name)) < $time;

    }


    protected function _findTemplate($name)
    {

        list($controller, $action) = explode(':', $name);

        if (file_exists($file = $this->path . '/' . $controller . '/' . $action . '.twig.html') === true) {
            return $file;
        }

        throw new \Twig_Error_Loader(sprintf('Unable to find template "%s" (looked into: %s).', $controller . '/' . $action . '.twig.html', $this->path));

    }

}