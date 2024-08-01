<?php

require dirname(dirname(__DIR__)) . '/config.php';

class SeasonControl
{
    // - - - ATTRIBUTES
    private $day;
    private $goingForward;
    private $dayStage;

    public function __construct()
    {
        $this->day = 0;
        $this->goingForward = true;
        $this->dayStage = 0;
    }

    // - - - PROPERTIES
    public function GetDay()
    {
        return $this->day;
    }
    public function SetDay(int $value)
    {
        $this->day = $value;
    }
    public function GetGoingForward()
    {
        return $this->goingForward;
    }
    public function SetGoingForward(bool $value)
    {
        $this->goingForward = $value;
    }

    public function GetDayStage()
    {
        return $this->dayStage;
    }

    public function SetDayStage(int $dayStage)
    {
        $this->dayStage = $dayStage;
    }

    private function UpdateDay(int $amountOfDays)
    {
        if($amountOfDays >= 0) // IF THE VALUE IS POSITIVE
        {
            if($this->goingForward === true) // ... AND THE YEAR IS GOING FORWARD
            {
                if( ($this->day + $amountOfDays) <= (cycle / 4))
                {
                    $this->day += $amountOfDays;
                    if($this->day === (cycle / 4))
                    {
                        $this->goingForward = false;
                    }
                }
                else // IF THERE'S AN EXCESS ... THE YEAR IS GOING TO GO BACKWARDS AFTER THIS.
                {
                    $excess = ($this->day + $amountOfDays) - (cycle / 4);
                    $this->day = (cycle / 4) - $excess;
                    $this->goingForward = false;
                }
            }
            else // ... ELSE, IF THE YEAR IS GOING BACKWARDS...
            {
                if( ($this->day - $amountOfDays) >= - (cycle / 4))
                {
                    $this->day -= $amountOfDays;
                    if($this->day === - (cycle / 4))
                    {
                        $this->goingForward = true;
                    }
                }
                else
                {
                    $excess = ($this->day - $amountOfDays) + (cycle / 4);
                    $this->day = -(cycle / 4) - $excess;
                    $this->goingForward = true;
                }
            }
        }
        elseif($amountOfDays <=0) // IF THE VALUE IS NEGATIVE
        {
            if($this->goingForward === true) // ... AND THE YEAR IS GOING FORWARD
            {
                if( ($this->day + $amountOfDays) >= - (cycle / 4))
                {
                    $this->day += $amountOfDays;
                }
                else // IF THERE'S AN EXCESS ... THE YEAR IS GOING TO GO BACKWARDS AFTER THIS.
                {
                    $excess = ($this->day + $amountOfDays) + (cycle / 4);
                    $this->day = - (cycle / 4) - $excess;
                    $this->goingForward = false;
                }
            }
            else // ... ELSE, IF THE YEAR IS GOING BACKWARDS...
            {
                if( ($this->day - $amountOfDays) <= (cycle / 4))
                {
                    $this->day -= $amountOfDays;
                    if($this->day === (cycle / 4))
                    {
                        $this->goingForward = true;
                    }
                }
                else
                {
                    $excess = ($this->day - $amountOfDays) - (cycle / 4);
                    $this->day = - (cycle / 4) - $excess;
                    $this->goingForward = true;
                }
            }
        } 
    }

    // - - - METHODS
    public function Tick()
    {        
        $previousDay = $this->day;
        $this->UpdateDay(1);
        echo 'Previous day: ' . $previousDay . "\nDay: " . $this->day . ' | Is moving towards: ' . ($this->goingForward ? 'Summer peak' : 'Winter peak') . "\n";        
    }

    public function DayStageTick()
    {
        $previousDay = $this->day;
        if($this->dayStage + 1 > 7)
        {
            $this->dayStage = 0;
            $this->day = $previousDay + 1;
        }
        else
        {
            $this->dayStage += 1;
        }
    }

    public function CustomTick(int $amountOfDays)
    {
        $previousDay = $this->day;
        $this->UpdateDay($amountOfDays);
        echo 'Previous day: ' . $previousDay . ' | Leap: ' . $amountOfDays . "\nDay: " . $this->day . ' | Is moving towards: ' . ($this->goingForward ? 'Summer peak' : 'Winter peak') . "\n";
    } 

    public function ReturnSeasonAsString($day = null, $goingForward = null)
    {
        $funcDay = $day == null ? $this->day : $day;
        $funcGoingForward = $goingForward == null ? $this->goingForward : ($goingForward == 'true' ? true : false);        

        if( ($funcDay >= seasons['Spring'][0] && $funcDay <= seasons['Spring'][1]) && $funcGoingForward == true)
        {
            $season = "Spring";
        }
        elseif( ($funcDay >= seasons['Summer'][0] && $funcGoingForward === true) || ($funcDay >= seasons['Summer'][1] && $funcGoingForward === false) )
        {
            $season = "Summer";
        }
        elseif( ($funcDay <= seasons['Fall'][0] && $funcDay >= seasons['Fall'][1]) && $funcGoingForward == false)
        {
            $season = "Fall";
        }
        else
        {
            $season = "Winter";
        }        
        return $season;
    }    
}

?>