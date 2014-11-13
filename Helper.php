<?php

namespace simpleframework;

require_once ROOT . '/vendor/Twig/ExtensionInterface.php';
require_once ROOT . '/vendor/Twig/Extension.php';

class Helper extends \Twig_Extension
{


    public function getName()
    {

        return 'simpleframeworkHelper';

    }


    public function getFunctions()
    {

        return array(
            'route'     => new \Twig_Function_Method($this, 'route'),
            'fullRoute' => new \Twig_Function_Method($this, 'fullRoute'),
        );

    }


    public function getFilters()
    {

        return array(
            'selected'   => new \Twig_Filter_Method($this, 'selected', array('is_safe' => array('all'))),
            'checked'    => new \Twig_Filter_Method($this, 'checked', array('is_safe' => array('all'))),
            'localeDate' => new \Twig_Filter_Method($this, 'date'),
        );

    }


    public function date($date, $format = '%c', $locale='fr_FR')
    {

        $date = new \DateTime($date);
        setlocale(LC_ALL, $locale);

        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
        }

        return utf8_encode(strftime(utf8_decode($format), $date->format('U')));

    }


    public function selected($var, $value)
    {

        if ($var == $value || (is_array($value) && in_array($var, $value) === true)) {
            return ' selected="selected"';
        }

        return '';

    }


    public function checked($var, $value=1)
    {

        if ($var == $value || (is_array($value) && in_array($var, $value) === true)) {
            return ' checked="checked"';
        } else {
            return '';
        }

    }


    public function route($var, $params=array())
    {

        return Kernel::getRoute($var, $params);

    }


    public function fullRoute($domain, $var, $params = array())
    {

        return Kernel::getFullRoute($domain, $var, $params);

    }


}
