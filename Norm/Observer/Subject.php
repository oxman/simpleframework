<?php

namespace simpleframework\Norm\Observer;

interface Subject
{

    public static function attach(Observer $observer);
    public static function detach(Observer $observer);
    public function notify($data);

}