<?php

class SimpleClock
{
    private $dataAccess;
    private $day;

    public function __construct($dataAccess)
    {
        $this->dataAccess = $dataAccess;
    }
}

?>