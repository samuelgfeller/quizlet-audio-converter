<?php


class Timer
{
    private $trackTime;
    private $startTimeStamp;
    
    public function __construct($trackTime)
    {
        $this->trackTime = $trackTime;
        $this->startTimeStamp = hrtime(true);
    }
    
    public function displayTime($message)
    {
        if ($this->trackTime === true) {
            echo '<p class="infoP">'.$message . ': <b>' . (hrtime(true) - $this->startTimeStamp) / 1e+9 . 's</b></p>';
        }
    }
}
