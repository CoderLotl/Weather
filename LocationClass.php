<?php

class Location
{
    #region ATTRIBUTES
    private $locationID;    // Discretional
    private $locationName;  // Discretional (too).
    private $locationType;  // 1: plains/meadows. 2: jungle. 3: woods/forest. 4: desert. 5: mountains. 6: swamp. 7: canyon. 8: lake. 9: taiga. 10: tundra. 11: tundra (deep)
    private $weather;       // [ 0: Not raining. 1: Dew. 2: Light rain. 3: rain. 4: downpour. 5: storm.]
    private $clouds;        // The amount of clouds in a range of of int that goes from 0 to 10.
    private $waterVapor;      // Amount of water in gaseous form. This is the Absolute Humidity.
    private $temperature;   // The location's current ambience temperature.
    private $localWater;    // The amount of water in liquid state at the location. Rivers, pools, lakes, whatever.    
    #endregion
    #region CONSTRUCTOR
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
    #endregion
    #region PROPERTIES
    /**
     * @param mixed $name id, name, type, weather, clouds, waterVapor, temperature, localWater.
     * @param mixed $value int, string, int, int, float, float, float, float.
     * 
     * @return [type]
     */
    public function __set($name, $value)
    {
        switch($name)
        {
            case 'id':
                $this->locationID = $value;
                break;
            case 'name':
                $this->locationName = $value;
                break;
            case 'type':
                $this->locationType = $value;
                break;
            case 'weather':
                $this->weather = $value;
                break;
            case 'clouds':
                $this->clouds = $value;
                break;
            case 'waterVapor':
                $this->waterVapor = $value;
                break;
            case 'temperature':
                $this->temperature = $value;
                break;
            case 'localWater':
                $this->localWater = $value;                
                break;
        }
    }

    /**
     * @param mixed $name id, name, type, weather, clouds, waterVapor, temperature, localWater.
     * 
     * @return [type] int, string, int, int, float, float, float, float.
     */
    public function __get($name)
    {        
        switch($name)
        {
            case 'id':
                return $this->locationID;                
            case 'name':
                return $this->locationName;                
            case 'type':
                return $this->locationType;                
            case 'weather':
                return $this->weather;                
            case 'clouds':
                return $this->clouds;
            case 'waterVapor':
                return $this->waterVapor;                
            case 'temperature':
                return $this->temperature;                
            case 'localWater':
                return $this->localWater;
        }        
    }
    #endregion    
    #region MISC
    
    public function __toString()
    {
        $clouds = $this->TranslateCloudsValue();
        $cloudsValue = $this->__get("clouds");
        $weatherMachine = new WeatherMachine();
        $relativeHumidity = $weatherMachine->CalcRelativeHumidity($this);
        $saturationPoint = $weatherMachine->CalcSaturationPoint($this->temperature);
        $saturationPointTemp = $weatherMachine->CalcSaturationPointTemp($this);
        $weather = $this->TranslateWeather();
        $weather = $this->TranslateWeather();
                
        return "Location ID: {$this->locationID}\nLocation Name: {$this->locationName}\nSky: {$clouds} | Clouds: {$cloudsValue}\nWeather: {$weather}\nTemperature: {$this->temperature}°C | " . ((($this->temperature * 9) / 5) + 32) . "°F"
        . "\nWater Vapor: {$this->waterVapor} g/m3 | Water Vapor Saturation point: {$saturationPoint} g/m3\nRelative Humidity: {$relativeHumidity}%" . 
        " | Saturation Temp for this humidity: {$saturationPointTemp}°C\nLocal Water: {$this->__get("localWater")}.";
    }
    #endregion
    #region METHODS

    /**
     * Reads the location's numeric value for weather and returns a string.
     * @return string
     */
    private function TranslateWeather()
    {
        // WEATHER STAGES: [ 0: Not raining. 1: Dew. 2: Drizzle. 3: Light rain. 4: rain. 5: downpour. 6: storm.]
        $returnValue = "";
        $weather = $this->__get("weather");
        switch($weather)
        {
            case 0:
                $returnValue = "Not raining";
                break;
            case 1:
                $returnValue = "Dew";
                break;
            case 2:
                $returnValue = "Drizzle";
                break;
            case 3:
                $returnValue = "Light rain";
                break;
            case 4:
                $returnValue = "Rain";
                break;
            case 5:
                $returnValue = "Downpour";
                break;
            case 6:
                $returnValue = "Storm";
                break;
        }

        return $returnValue;
    }

    /**
     * Reads the location's numeric value for the clouds and returns a string.
     * @return string
     */
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
            case ($clouds >= 10 && $clouds < 15):
                $returnValue = "A few clouds";
                break;
            case ($clouds >= 15 && $clouds < 20):
                $returnValue = "Fairly clouded";
                break;
            case ($clouds >= 20 && $clouds < 25):
                $returnValue = "Clouded";
                break;
            case ($clouds >= 25 && $clouds < 30):
                $returnValue = "Very clouded";
                break;
            case ($clouds >= 30):
                $returnValue = "Heavily clouded";
                break;
        }
        return $returnValue;
    }
    #endregion
}

?>