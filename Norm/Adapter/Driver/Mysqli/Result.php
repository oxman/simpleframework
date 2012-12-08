<?php

namespace simpleframework\Norm\Adapter\Driver\Mysqli;

require_once ROOT . '/vendor/simpleframework/Norm/Adapter/DatabaseResult.php';


class Result implements \simpleframework\Norm\Adapter\DatabaseResult
{


    protected $_result;


    public function __construct(\mysqli_result $result)
    {

        $this->_result = $result;
        $this->_result->rand = rand(0,50);

    }


    public function fetchArray()
    {

        return $this->_result->fetch_array(MYSQLI_NUM);

    }


    public function fetchFields()
    {

        $fields = $this->_result->fetch_fields();

        if ($fields === false) {
            return null;
        }

        return $fields;

    }


}