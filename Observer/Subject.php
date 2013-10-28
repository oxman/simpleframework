<?php

namespace simpleframework\Observer;

interface Subject
{

    public static function attach(Observer $observer);
    public static function detach(Observer $observer);
    public function notify($data);

}