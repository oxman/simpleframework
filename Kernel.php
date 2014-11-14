<?php

namespace simpleframework;

require_once ROOT . "/vendor/simpleframework/Observer/Subject.php";

class Kernel implements Observer\Subject
{

    protected $_routes;
    protected $_controller;
    protected $_action;
    protected static $_config = array();
    protected static $_currentRoute;
    protected static $_observers = array();
    public static $currentCall = array();


    /** Observer **/

    public function getObservers()
    {

        return self::$_observers;

    }


    public static function attach(Observer\Observer $observer)
    {

        self::$_observers[] = $observer;

    }


    public static function detach(Observer\Observer $observer)
    {

        $key = array_search($observer, self::$_observers);
        unset(self::$_observers[$key]);

    }


    public function notify($data)
    {

        foreach(self::$_observers as $observer) {
            $observer->update($data);
        }

    }


    protected function _loadEnv($env='prod')
    {

        define('SIMPLEFRAMEWORK_ENV', $env);

        $config = array();

        foreach(glob(ROOT . '/app/config/' . SIMPLEFRAMEWORK_ENV . '/*.php') as $file) {
            require_once $file;
        }

        foreach(glob(ROOT . '/app/config/*.php') as $file) {
            require_once $file;
        }

        self::$_config = $config;

    }


    public function init($env)
    {

        require_once ROOT . '/vendor/simpleframework/Autoloader.php';
        require_once ROOT . '/vendor/simpleframework/ModelMeta.php';
        require_once ROOT . '/vendor/simpleframework/Model.php';
        require_once ROOT . '/vendor/simpleframework/Query.php';
        require_once ROOT . '/vendor/simpleframework/Controller.php';

        require_once ROOT . '/vendor/simpleframework/vendor/Norm/Query.php';
        require_once ROOT . '/vendor/simpleframework/vendor/Norm/Model.php';
        require_once ROOT . '/vendor/simpleframework/vendor/Norm/Metadata.php';

        $this->_loadEnv($env);

        $connections = Kernel::getConfig('db');

        $configuration = \Norm\Configuration::getInstance();
        $configuration->setModel(ROOT . '/app/model');

        if ($env === 'prod') {
            $configuration->setCache(ROOT . '/tmp/cache/norm.txt');
        }

        foreach($connections as $key => $config) {
            $configuration->setConnection(
                $config['hostname'],
                $config['username'],
                $config['password'],
                $config['database'],
                $key);
        }

        Autoloader::register();

        new ModelMeta;

    }


    public function start($env)
    {

        $this->init($env);

        self::$currentCall['env'] = $env;
        self::$currentCall['type'] = 'web';

        $this->_dispatch();
        $class = $this->_call(self::$currentCall['controller'],
                              self::$currentCall['action'],
                              self::$currentCall['params'],
                              self::$currentCall['dir']);
        $twig  = $this->_initView();
        $this->_view($twig, $class);

    }


    public function startCli($env, $controller, $action, $params, $dir)
    {

        self::$currentCall = array(
            'env'        => $env,
            'type'       => 'cli',
            'controller' => $controller,
            'action'     => $action,
            'params'     => $params,
            'dir'        => $dir
        );

        $this->init($env);
        $class = $this->_call(self::$currentCall['controller'],
                              self::$currentCall['action'],
                              self::$currentCall['params'],
                              self::$currentCall['dir']);

        return $class;

    }


    public static function getConfig($key, $subkey=null)
    {

        if ($subkey === null) {
            return self::$_config[$key];
        } elseif (isset(self::$_config[$key][$subkey]) === true) {
            return self::$_config[$key][$subkey];
        }

        return false;

    }


    public static function getCurrentRoute()
    {

        return self::$_currentRoute;

    }


    public static function getRoute($key, $params=array())
    {

        return self::_makeRoute($key, $params);

    }


    public static function getFullRoute($domain, $key, $params=array())
    {

        return self::_makeRoute($key, $params, $domain);

    }


    protected static function _makeRoute($key, $params=array(), $domain=false)
    {

        if (isset(self::$_config['route'][$key]) === false) {
            return false;
        }

        $route = self::$_config['route'][$key][0];

        // match all parts
        preg_match_all('#\(?([^()]+)\)?#', $route, $groups, PREG_SET_ORDER);

        $route = "";

        foreach($groups as $group) {
            // match all optionals group
            preg_match_all('#\(((.+?)/)?(.+?)\)+#', $group[0], $optGroups, PREG_SET_ORDER);

            // its a standard part, not an optional group
            if (count($optGroups) === 0) {
                $group[3] = '';
                $optGroups = array($group);
                $isOpt = false;
            } else {
                $isOpt = true;
            }

            foreach($optGroups as $optGroup) {
                $subRoute = $optGroup[1] . $optGroup[3];
                // match default value
                preg_match_all('#((@?\{([^=}]+)(=([^}]+))?\})+)#', $subRoute, $vars, PREG_SET_ORDER);

                $found = 0;

                foreach($vars as $var) {

                    if (isset($params[$var[3]]) === true) {
                        $subRoute = str_replace($var[2], urlencode($params[$var[3]]), $subRoute);
                        $found++;
                    } else if (isset($var[5]) === true) {
                        $subRoute = str_replace($var[2], urlencode($var[5]), $subRoute);
                    } else {
                        $subRoute = str_replace($var[2], '', $subRoute);
                    }
                }

                if ($found > 0 || $isOpt === false) {
                    $route .= $subRoute;
                }

            }
        }

        if ($domain !== false) {
            $route = 'http://' . self::getConfig('domain', $domain) . $route;
        }

        return $route;

    }

    protected function _dispatch()
    {

        foreach(self::$_config['route'] as $key => $route) {

            $domain = false;
            if (preg_match('/^(@\{(.+?)\})/', $route[0], $params) > 0) {
                $domain   = $params[2];
                $route[0] = str_replace($params[1], '', $route[0]);
            }

            unset($params);

            $patternRoute = preg_replace('#\((.+?)\)#', "($1)?", $route[0]);
            $patternRoute = preg_replace('#/{([^/]+)=[^}]+\}#', "(/|$)(?P<$1>[^/]+)?", $patternRoute);
            $patternRoute = preg_replace('#\{([^}]+?)\}#', "(?P<$1>[^/]+)", $patternRoute);
            $patternRoute = '#^' . $patternRoute . '/?$#';

            $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

            if (($domain === false || isset($_SERVER['HTTP_HOST']) === true
                && self::$_config['domain'][$domain] === $_SERVER['HTTP_HOST'])
                && preg_match($patternRoute, $url, $params) > 0) {
                break;
            }

        }

        self::$_currentRoute = $key;

        foreach($params as $key => &$match) {
            if (is_int($key) === true || $match === "") {
                unset($params[$key]);
            } else {
                $match = htmlspecialchars($match, ENT_NOQUOTES, 'UTF-8');
            }
        }

        preg_match_all('#\{\*?([^}]+?)=([^}]+?)\}#', $route[0], $defaultParams, PREG_SET_ORDER);

        foreach($defaultParams as $values) {
            if (isset($params[$values[1]]) === false ||
                $params[$values[1]] === '') {
                $params[$values[1]] = $values[2];
            } else {
                $params[$values[1]] = urldecode($params[$values[1]]);
            }
        }

        list($controller, $action) = explode(':', $route[1]);

        if (basename($controller) !== $controller) {
            $dir = dirname($controller) . '/';
            $controller = basename($controller);
        } else {
            $dir = '/';
        }

        $this->_callback   = $route[1];
        $this->_controller = $controller;
        $this->_action     = $action;

        self::$currentCall = array_merge(self::$currentCall, array(
            'controller' => $controller,
            'action'     => $action,
            'params'     => $params,
            'dir'        => $dir
            ));

    }


    protected function _call($controller, $action, $params, $dir)
    {

        if (function_exists('newrelic_name_transaction') === true) {
                newrelic_name_transaction($controller . '/' . $action);
        }

        $controllerCamelCase = ucfirst(preg_replace_callback('/(_[a-zA-Z])/', function ($match) { return ucfirst($match[1][1]); }, strtolower($controller)));

        $className = $controllerCamelCase . 'Controller';

        require_once ROOT . '/app/controller/' . $dir . $className . '.php';

        if ($dir === '/') {
            $class = new $className;
        } else {
            $tmp   = '\\' . rtrim($dir, '/') . '\\' . $className;
            $class = new $tmp();
        }

        call_user_func(array($class, 'pre'), $params);
        call_user_func(array($class, $action), $params);
        call_user_func(array($class, 'post'), $params);

        return $class;

    }


    protected function _initView()
    {

        require_once ROOT . '/vendor/simpleframework/Helper.php';
        require_once ROOT . '/vendor/simpleframework/LoaderTemplate.php';
        require_once ROOT . '/vendor/Twig/Autoloader.php';

        \Twig_Autoloader::register();

        $loader = new LoaderTemplate(ROOT . '/app/view');

        if (defined('TEMPLATE_CACHE') === false && in_array(SIMPLEFRAMEWORK_ENV, array('dev', 'local')) === true
            || defined('TEMPLATE_CACHE') === true && TEMPLATE_CACHE === false) {
            $autoReload = true;
        } else {
            $autoReload = false;
        }

        $options = array(
          'cache'       => ROOT . '/tmp/cache/template',
          'auto_reload' => $autoReload
        );

        if (in_array(SIMPLEFRAMEWORK_ENV, array('dev', 'local')) === true
            || defined('TEMPLATE_DEBUG') === true && TEMPLATE_DEBUG === true) {
            $options['debug'] = true;
        }

        $twig = new \Twig_Environment($loader, $options);

        if (class_exists('\Twig_Extension_Debug') === true) {
            $twig->addExtension(new \Twig_Extension_Debug());
        }

        if (class_exists('\Twig_Extension_StringLoader') === true) {
            $twig->addExtension(new \Twig_Extension_StringLoader());
        }

        return $twig;

    }


    protected function _view($twig, $class)
    {

        $params  = $class->view;
        $helpers = $class->helper;

        if ($helpers !== null) {

            if (is_array($helpers) === false) {
                $helpers = array($helpers);
            }

            foreach($helpers as $helper) {
                $twig->addExtension($helper);
            }
        }

        $twig->addExtension(new Helper());

        if ($class->viewName === null) {
            $template = $twig->loadTemplate($this->_callback);
        } else {
            $template = $twig->loadTemplate($class->viewName);
        }

        echo $template->render($params);

    }


}
