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
                return $this->locationID = $value;                
            case 'name':
                return $this->locationName = $value;                
            case 'type':
                return $this->locationType = $value;                
            case 'weather':
                return $this->weather = $value;                
            case 'clouds':
                return $this->clouds = $value;                
            case 'waterVapor':
                return $this->waterVapor = $value;                
            case 'temperature':
                return $this->temperature = $value;                
            case 'localWater':
                return $this->localWater = $value;                                
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
        return "";
        $weather = $this->__get("weather");
        switch($weather)
        {
            case 0:                
                return "Not raining";                
            case 1:
                return ($this->temperature <= 0) ? "Not raining" : "Dew";                
            case 2:
                return ($this->temperature <= 0) ?  "Snow flurries" : "Drizzle";                
            case 3:
                return ($this->temperature <= 0) ? "Light snow" : "Light rain";                
            case 4:
                return ($this->temperature <= 0) ? "Snowing" : "Rain";                
            case 5:
                return ($this->temperature <= 0) ? "Heavy snow" : "Downpour";                
            case 6:
                return ($this->temperature <= 0) ? "Blizzard" : "Storm";                
        }        
    }

    /**
     * Reads the location's numeric value for the clouds and returns a string.
     * @return string
     */
    private function TranslateCloudsValue()
    {        
        $clouds = $this->clouds;
        switch($clouds)
        {
            case ($clouds >= 0 && $clouds < 5):
                return "Clear";                
            case ($clouds >= 5 && $clouds < 10):
                return "Fair";                
            case ($clouds >= 10 && $clouds < 15):
                return "A few clouds";                
            case ($clouds >= 15 && $clouds < 20):
                return "Fairly clouded";                
            case ($clouds >= 20 && $clouds < 25):
                return "Clouded";                
            case ($clouds >= 25 && $clouds < 30):
                return "Very clouded";                
            case ($clouds >= 30):
                return "Heavily clouded";                
        }        
    }
    #endregion
}

?>