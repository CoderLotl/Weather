<?php

class Location
{
    // - - - ATTRIBUTES
    private $locationID;    // Discretional
    private $locationName;  // Discretional (too).
    private $locationType;  // 1: plains/meadows. 2: jungle. 3: woods/forest. 4: desert. 5: mountains. 6: swamp. 7: canyon. 8: lake. 9: taiga. 10: tundra. 11: tundra (deep)
    private $weather;       // [ 0: Not raining. 1: Dew. 2: Light rain. 3: rain. 4: downpour. 5: storm.]
    private $clouds;        // The amount of clouds in a range of of int that goes from 0 to 10.
    private $waterVapor;      // Amount of water in gaseous form. This is the Absolute Humidity.
    private $temperature;   // The location's current ambience temperature.
    private $localWater;    // The amount of water in liquid state at the location. Rivers, pools, lakes, whatever.    

    // - - - CONSTRUCTOR
    public function __construct(int $locationID, string $locationName, int $locationType, int $weather, float $clouds, float $waterVapor, int $temperature, float $localWater)
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
        $cloudsValue = $this->GetClouds();
        $weatherMachine = new WeatherMachine();
        $relativeHumidity = $weatherMachine->CalcRelativeHumidity($this);
        $saturationPoint = $weatherMachine->CalcSaturationPoint($this->temperature);
        $saturationPointTemp = $weatherMachine->CalcSaturationPointTemp($this);
        $weather = $this->TranslateWeather();

        return "Location ID: {$this->locationID}\nLocation Name: {$this->locationName}\nSky: {$clouds} | Clouds: {$cloudsValue}\nWeather: {$weather}\nTemperature: {$this->temperature}°C | " . ((($this->temperature * 9) / 5) + 32) . "°F"
        . "\nWater Vapor: {$this->waterVapor} g/m3 | Water Vapor Saturation point: {$saturationPoint} g/m3\nRelative Humidity: {$relativeHumidity}%" . 
        " | Saturation Temp for this humidity: {$saturationPointTemp}°C\nLocal Water: {$this->GetLocalWater()}.";
    }

    // - - - METHODS
    private function TranslateWeather()
    {
        // [ 0: Not raining. 1: Dew. 2: Light rain. 3: rain. 4: downpour. 5: storm.]
        $returnValue = "";
        $weather = $this->GetWeather();        
        switch($weather)
        {
            case 0:
                $returnValue = "Not raining.";
                break;
            case 1:
                $returnValue = "Dew.";
                break;
            case 2:
                $returnValue = "Light rain.";
                break;
            case 3:
                $returnValue = "Raining.";
                break;
            case 4:
                $returnValue = "Downpour.";
                break;
            case 5:
                $returnValue = "Storm.";
                break;                
        }

        return $returnValue;
    }

    private function TranslateCloudsValue()
    {
        $returnValue = "";
        $clouds = $this->clouds;
        switch($clouds)
        {
            case ($clouds >= 0 && $clouds < 5):
                $returnValue = "Clear";
                break;
            case ($clouds >= 5 && $clouds < 10):
                $returnValue = "Fair";
                break;
            case ($clouds >= 10 && $clouds < 20):
                $returnValue = "A few clouds";
                break;
            case ($clouds >= 20 && $clouds < 30):
                $returnValue = "Fairly clouded";
                break;
            case ($clouds >= 30 && $clouds < 50):
                $returnValue = "Clouded";
                break;
            case ($clouds >= 50 && $clouds < 70):
                $returnValue = "Very clouded";
                break;
            case ($clouds >= 70 && $clouds <= 100):
                $returnValue = "Heavily clouded";
                break;
        }
        return $returnValue;
    }
}

?>
