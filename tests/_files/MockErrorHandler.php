<?php
class MockErrorHandler implements Observer\Pattern\Subject
{
    const ERRORMSG = "errortest";

    public function attach(Observer\Pattern\Observer ...$obs) { }

    public function detach(Observer\Pattern\Observer ...$obs) { }

    public function notify() { }

    public function getError()
    {
        return self::ERRORMSG;
    }
}
