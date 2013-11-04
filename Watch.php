<?php

namespace simpleframework;

require_once ROOT . "/vendor/simpleframework/Observer/Observer.php";
require_once ROOT . "/vendor/simpleframework/Norm/Query.php";
require_once ROOT . "/vendor/Watch.php";

class Watch
{

    public function __construct($applicationId, $applicationSecret)
    {

        \Watch\Watch::setApplicationId($applicationId);
        \Watch\Watch::setApplicationSecret($applicationSecret);

        \simpleframework\Norm\Query::attach(new WatchObserver);
        set_error_handler(array($this, 'error'));
        register_shutdown_function(array($this, 'end'));

    }


    public function setData(array $data)
    {

        \Watch\Watch::context(null, $data);

    }


    public function error($errno, $errstr, $errfile, $errline)
    {

        \Watch\Watch::error($errfile, array('numero' => $errno, 'message' => $errstr, 'line' => $errline));

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


class WatchObserver implements \simpleframework\Observer\Observer
{

    public function update($data)
    {

        \Watch\Watch::measure('query', $data);

    }

}

