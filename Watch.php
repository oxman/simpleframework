<?php

namespace simpleframework;

require_once ROOT . "/vendor/simpleframework/vendor/Norm/Observer/Observer.php";
require_once ROOT . "/vendor/simpleframework/vendor/Norm/Query.php";
require_once ROOT . "/vendor/Watch.php";

class Watch
{

    public function __construct($applicationId, $applicationSecret)
    {

        \Watch\Watch::setApplicationId($applicationId);
        \Watch\Watch::setApplicationSecret($applicationSecret);

        \Norm\Query::attach(new WatchObserver);
        set_error_handler(array($this, 'error'));
        register_shutdown_function(array($this, 'end'));

        $data = array();
        if (isset($_SERVER['HTTP_HOST']) === true) {
            $data['hostname'] = $_SERVER['HTTP_HOST'];
        }

        if (isset($_SERVER['REQUEST_URI']) === true) {
            $data['url'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }

        if (count($data) > 0) {
            \Watch\Watch::context(null, $data);
        }

    }


    public function setData(array $data)
    {

        \Watch\Watch::context(null, $data);

    }


    public function error($errno, $errstr, $errfile, $errline)
    {

        \Watch\Watch::error($errfile, array('numero' => $errno, 'message' => $errstr, 'line' => $errline));

        if (SIMPLEFRAMEWORK_ENV !== "prod") {
            return false;
        }

        return true;

    }


    public function end()
    {

        $call = \simpleframework\Kernel::$currentCall;
        $data = array('type' => $call['type']);

        if (count($call['params']) > 0) {
            $data['params'] = $call['params'];
        }

        \Watch\Watch::context($call['controller'] . ':' . $call['action'], $data);
        \Watch\Watch::send();

    }


}


class WatchObserver implements \Norm\Observer\Observer
{

    public function update($data)
    {

        \Watch\Watch::measure('query', $data);

    }

}

