<?php

namespace simpleframework\Norm\Adapter;

interface DatabaseResult
{

    public function fetchArray();
    public function fetchFields();

}