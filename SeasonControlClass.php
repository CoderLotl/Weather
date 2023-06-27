<?php

class SeasonControl
{
    // - - - ATTRIBUTES
    private $day;
    private $goingForward;

    public function __construct()
    {
        $this->day = 0;
        $this->goingForward = true;
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

    private function UpdateDay(int $amountOfDays)
    {
        if($amountOfDays >= 0) // IF THE VALUE IS POSITIVE
        {
            if($this->goingForward === true) // ... AND THE YEAR IS GOING FORWARD
            {
                if( ($this->day + $amountOfDays) <= 42)
                {
                    $this->day += $amountOfDays;
                    if($this->day === 42)
                    {
                        $this->goingForward = false;
                    }
                }
                else // IF THERE'S AN EXCESS ... THE YEAR IS GOING TO GO BACKWARDS AFTER THIS.
                {
                    $excess = ($this->day + $amountOfDays) - 42;
                    $this->day = 42 - $excess;
                    $this->goingForward = false;
                }
            }
            else // ... ELSE, IF THE YEAR IS GOING BACKWARDS...
            {
                if( ($this->day - $amountOfDays) >= -42)
                {
                    $this->day -= $amountOfDays;
                    if($this->day === -42)
                    {
                        $this->goingForward = true;
                    }
                }
                else
                {
                    $excess = ($this->day - $amountOfDays) + 42;
                    $this->day = -42 - $excess;
                    $this->goingForward = true;
                }
            }
        }
        elseif($amountOfDays <=0) // IF THE VALUE IS NEGATIVE
        {
            if($this->goingForward === true) // ... AND THE YEAR IS GOING FORWARD
            {
                if( ($this->day + $amountOfDays) >= -42)
                {
                    $this->day += $amountOfDays;
                }
                else // IF THERE'S AN EXCESS ... THE YEAR IS GOING TO GO BACKWARDS AFTER THIS.
                {
                    $excess = ($this->day + $amountOfDays) + 42;
                    $this->day = -42 - $excess;
                    $this->goingForward = false;
                }
            }
            else // ... ELSE, IF THE YEAR IS GOING BACKWARDS...
            {
                if( ($this->day - $amountOfDays) <= 42)
                {
                    $this->day -= $amountOfDays;
                    if($this->day === 42)
                    {
                        $this->goingForward = true;
                    }
                }
                else
                {
                    $excess = ($this->day - $amountOfDays) - 42;
                    $this->day = -42 - $excess;
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

    public function CustomTick(int $amountOfDays)
    {
        $previousDay = $this->day;
        $this->UpdateDay($amountOfDays);
        echo 'Previous day: ' . $previousDay . ' | Leap: ' . $amountOfDays . "\nDay: " . $this->day . ' | Is moving towards: ' . ($this->goingForward ? 'Summer peak' : 'Winter peak') . "\n";
    } 

    public function ReturnSeasonAsString()
    {
        if( ($this->day >= 22 && $this->goingForward === true) || ($this->day >= 21 && $this->goingForward === false) )
        {
            $season = "Summer";
        }
        elseif( ($this->day >= -20 && $this->day <= 21) && $this->goingForward == true)
        {
            $season = "Spring";
        }
        elseif( ($this->day <= 20 && $this->day >= -21) && $this->goingForward == false)
        {
            $season = "Fall";
        }
        else
        {
            $season = "Winter"; // Winter goes from -22 , up to -42, to -21 again.
        }        
        return $season;
    }    
}

?>