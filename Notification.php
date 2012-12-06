<?php

namespace simpleframework;

class Notification
{

    /**
     * Special key treated by Watch :
     *      global, sql, curl, error
     */
    protected static $_notifications = array();

    public static function init()
    {

        register_shutdown_function('simpleframework\Notification::forceCall');

    }


    public static function add($key, $callback, $params=array())
    {

        if (isset(self::$_notifications[$key]) === false) {
            self::$_notifications[$key] = array();
        }

        self::$_notifications[$key][] = array($callback, $params);

    }


    public static function call($key, $params=array())
    {

        if (in_array($key, array_keys(self::$_notifications)) === true) {
            foreach(self::$_notifications[$key] as $callback) {
                if (is_array($callback[1]) === false) {
                    $callback[1] = array($callback[1]);
                }

                if ($key === "global") {
                    if (PHP_SAPI === 'cli') {
                        $params[0] = $params[0] . '[cli]';
                    } else {
                        $params[0] = $_SERVER['SERVER_NAME'];
                    }
                } else {
                    $params = array($params);
                }

                call_user_func_array($callback[0], array_merge($callback[1], $params));
            }
        }

    }


    public static function forceCall()
    {

        if (isset(Kernel::$currentCall['controller']) === false && isset(Kernel::$currentCall['action']) === false) {
            $key = "undefined";
        } else {
            $key = Kernel::$currentCall['controller'] . '/' . Kernel::$currentCall['action'];
        }

        if (isset(Kernel::$currentCall['params']) === true) {
            $params = Kernel::$currentCall['params'];
        } else {
            $params = array();
        }

        self::call('end', array('key' => $key,
            'params' => $params));

    }


}