<?php

class SeasonControl
{
    // - - - ATTRIBUTES
    private $day;
    private $goingForward;
    
    // - - - CONSTRUCTOR
    public function __construct()
    {     
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

    // - - - METHODS
    public function Tick(WeatherSystemDataAccess $WeatherSystemdataAccess, string $table)
    {        
        $previousDay = $this->day;
        if($this->goingForward == true)
        {
            if($this->day != 42)
            {
                $this->day++;
            }
            else
            {
                $this->goingForward = false;
                $this->day--;
            }
        }
        else
        {
            if($this->day != -42)
            {
                $this->day--;
            }
            else
            {
                $this->goingForward = true;
                $this->goingForward++;
            }
        }
        echo 'Previous day: ' . $previousDay . "\nDay: " . $this->day . ' | Is moving towards: ' . ($this->goingForward ? 'Summer peak' : 'Winter peak') . "\n";
        $WeatherSystemdataAccess->WriteSeasonDataToDB($this, $table);
    }

    public function CustomTick(int $amountOfDays, WeatherSystemDataAccess $WeatherSystemdataAccess, string $table)
    {
        $previousDay = $this->day;
        if($amountOfDays >= 0) // IF THE VALUE IS POSITIVE
        {
            if($this->goingForward == true) // ... AND THE YEAR IS GOING FORWARD
            {
                if( ($this->day + $amountOfDays) <= 42)
                {
                    $this->day += $amountOfDays;
                    if($this->day == 42)
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
                    if($this->day == -42)
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
            if($this->goingForward == true) // ... AND THE YEAR IS GOING FORWARD
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
                    if($this->day == 42)
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

        $WeatherSystemdataAccess->WriteSeasonDataToDB($this, $table);
        echo 'Previous day: ' . $previousDay . ' | Leap: ' . $amountOfDays . "\nDay: " . $this->day . ' | Is moving towards: ' . ($this->goingForward ? 'Summer peak' : 'Winter peak') . "\n";
    }

    public function ReturnSeasonAsString()
    {
        if( ($this->day >= 22 && $this->goingForward == true) || ($this->day >= 21 && $this->goingForward == false) )
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