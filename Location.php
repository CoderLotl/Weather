<?php

class Location
{
    // - - - ATTRIBUTES
    private $locationID;    // Discretional
    private $locationName;
    private $locationType;  // 1: plains/meadows. 2: jungle. 3: woods/forest. 4: desert. 5: mountains. 6: swamp. 7: canyon. 8: lake. 9: taiga. 10: tundra. 11: tundra (deep)
    private $weather;       // [-2: Very sunny. -1: Sunny. 0: Not raining. 1: Dew. 2: Light rain. 3: rain. 4: downpour. 5: storm.]
    private $clouds;        // The amount of clouds in a range of of int that goes from 0 to 10.
    private $waterVapor;      // Amount of water in gaseous form. This is the Absolute Humidity.
    private $temperature;   // The location's current ambience temperature.
    private $localWater;    // The amount of water in liquid state at the location. Rivers, pools, lakes, whatever.    

    // - - - CONSTRUCTOR
    public function __construct(int $locationID, string $locationName, int $locationType, int $weather, int $clouds, int $waterVapor, Int $temperature, int $localWater)
    {
        $this->locationID = $locationID;
        $this->locationName = $locationName;
        $this->locationType = $locationType;
        $this->weather = $weather;
        $this->clouds = $clouds;
        $this->waterVapor = $waterVapor;
        $this->temperature = $temperature;
        $this->localWater = $localWater;
    }

    // - - - PROPERTIES
    public function SetID($value)
    {
        $this->locationID = $value;
    }
    public function GetID()
    {
        return $this->locationID;
    }
    public function SetName($value)
    {
        $this->locationName = $value;
    }
    public function GetName()
    {
        return $this->locationName;
    }
    public function SetTemperature($value)
    {
        $this->temperature = $value;
    }
    public function GetTemperature()
    {
        return $this->temperature;
    }
    public function SetLocationType($value)
    {
        $this->locationType = $value;
    }
    public function GetLocationType()
    {
        return $this->locationType;
    }
    public function SetWeather($value)
    {
        $this->weather = $value;
    }
    public function GetWeather()
    {
        return $this->weather;
    }
    public function SetClouds($value)
    {
        $this->clouds = $value;
    }
    public function GetClouds()
    {
        return $this->clouds;
    }
    public function SetWaterVapor($value)
    {
        $this->waterVapor = $value;
    }
    public function GetWaterVapor()
    {
        return $this->waterVapor;
    }
    public function SetLocalWater($value)
    {
        $this->localWater = $value;
    }
    public function GetLocalWater()
    {
        return $this->localWater;
    }


    // - - - MISC
    public function __toString()
    {
        $clouds = $this->TranslateCloudsValue();
        $weatherMachine = new WeatherMachine();
        $relativeHumidity = $weatherMachine->CalcRelativeHumidity($this);
        $saturationPoint = $weatherMachine->CalcSaturationPoint($this->temperature);
        $saturationPointTemp = $weatherMachine->CalcSaturationPointTemp($this);

        return "Location ID: {$this->locationID}\nLocation Name: {$this->locationName}\nSky: {$clouds}\nTemperature: {$this->temperature}°C | " . ((($this->temperature * 9) / 5) + 32) . "°F"
        . "\nWater Vapor: {$this->waterVapor} g/m3 | Water Vapor Saturation point: {$saturationPoint} g/m3\nRelative Humidity: {$relativeHumidity}%" . 
        " | Saturation Temp for this humidity: {$saturationPointTemp}°C";
    }

    // - - - METHODS
    private function TranslateCloudsValue()
    {
        $returnValue = "";
        switch($this->clouds)
        {
            case 0:
                $returnValue = "Clear";
                break;
            case 1: case 2:
                $returnValue = "Fair";
                break;
            case 3: case 4:
                $returnValue = "A few clouds";
                break;
            case 5: case 6:
                $returnValue = "Fairly clouded";
                break;
            case 7: case 8:
                $returnValue = "Clouded";
                break;
            case 9: case 10:
                $returnValue = "Heavily clouded";
                break;
        }
        return $returnValue;
    }
}

?>