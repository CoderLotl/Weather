<?php

/*
*   NOTES:
*
*   - To add: humidity, seasons, precipitation.
*   
*   
*   - When the season is hotter, the air is not cold enough for clouds to form due to the fact they need to condensate. Therefore, it rains less.
*       The oppossite happens when the season is colder. Colder season = more clouds = more rain.
*   - If the air is hotter, the water will evaporate faster and create higher lower humidity.
*
*   - Clouds should go from 0 to 10. Humidity at or above 60 would increase 1. Humidity at or above 80 would increase 2.
*       Humidity at or below 40 would decrease 1. Humidity at or below 20 would decrease 2.
*   - Clouds between 0 and 2 would make it Clear. Between 2 and 4 Some Clouds, 4 and 6 Fairly Clouded, 6 and 8 Clouded, 8 and 10 Heavily Clouded.
*   - During the day humidity is lower than during night.
*   - Dew?...
*/

/*
- The hotter the air, the faster water evaporates but the harder it's for water vapor to condensate, so more air humidity but less clouds.
    So a hotter season makes clouds need a higher humidity to form.
- The colder the air, the less evaporation and the less humidity you get, but the easier it is for water vapor to condensate, therefore less humidity is
    needed for clouds to form and it's easier to get rain when it's colder.
- If the air gets too cold, too, humidity condensates before even forming clouds and falls to the ground. So rains increase the immediate humidity, but 
    in the long term help to reduce it since the air cools with the rain.
- Also the more dry the air is, the more the water droplets evaporate before reaching the ground. The opposite happens when the air is moister. 

So if the season is cold, it's probable that you may get more clouds and more rain than in a hot season, but cloudy days are less prone to produce rain when it's cold than when it's hot.
*/

/*

SEASON: it could be just a number that drifts from -10 to 10, instead of a rough change.

tentative:

humidity for cloud formation:

- hot season: 70%.
-- 60 for slow.
-- humidity increases really fast.
-- humidity decreases really slow.
-- increased chance of rain when it's cloudy (which is rarer than in cold season). This is due to higher humidity.

** reduced chance of rain overall due to requiring high humidity for raining in order to compensate with the hot weather. (not a mechanic, just an observation)
    If the place has a lot of water, rains would be common. Otherwise, the hot seasons could experiment few rains.
    If the place is too dry it could be hellish in hot seasons.

==============================================================

- cold season: 30%.
-- 20 for slow.
-- humidity increases really slow.
-- humidity decreases really fast.
-- reduced chance of rain when it's cloudy (which is more common than in hot season). This is due to lower humidity.

** increased chance of rain overall due to requiring cold weather for raining in order to compensate with the low humidity. (not a mechanic, just an observation)
    If the place has a lot of water, cold seasons could be a hell of rains. Otherwise it would be fine.
    If the place is too dry it could experiment... Snow?...

==============================================================

+++ rain always reduces temperature.
+++ rain always increases humidity.

Now the weather starts as clear, the temperature as an average point for a season = 0, the same with humidity (except you want some specific value). The local water starts at whatever you want.

Based on that, the new temperature is set depending on the previous weather and season, and location type.
The new weather is set based on the previous humidity and new temperature.
The new humidity is based on the new weather and new temperature. (if it rains, you get more humidity. If not, it all depends...)
The new local water is based on the change of humidity and weather (if the humidity goes down because of condensation, you get more local water. If it goes up because of evaporation, you get less local water. - If you get less clouds because of precipitation, you have more water.)



*/
// - - - [ TEST ] - - -


class WeatherMachine
{
    public function CalcTemperature($season, Location $location)
    {        
        $temperature = $this->CalcTempBySeason($season, $location->GetLocationType(), $location->GetWeather());
        
    }

    private function CalcTempBySeason(int $season, int $locationType, int $weather)
    {
        // This part calculates the average temperatures.

        $tunning = 0;  // changing this amplitude affects the top and bottom limits.
                        // Increasing this var moves the range to the positive side. Reducing it does the opposite.
        $amplitude = 0;    // changing this amplitude affects the top and bottom limits.
                        // Increasing this var expands the temp range both ways. Reducing it does the opposite.
        $plus = 0;      // This factor adds a plus.

        $deviation = 0;
        
        $timeDivider = 6; // Since the system has been designed to work with units of 7 days and the seasons have 42 days (6 weeks), this is
                          // an important factor. If the seasons's length ever changes, you can tune it here.
        
        switch($locationType)
        {
            case 1: // Plains / meadows
                $tunning = 12; $amplitude = 2.6; $plus = 0; // 21 to 85 F, -6 to 29 C. - Deviation should go a lil bit up and down. - Night and day changes are small.
                $deviation = 2;
                break;
            case 2: // Jungles 
                $tunning = 23; $amplitude = 0.3; // 68 to 77 F, 20 to 25 C. - Deviation should only go up. - Night and day changes are small.
                break;
            case 3: // Woods / forests
                $tunning = 11; $amplitude = 2.30; $plus = -3; // 17 to 73 F, -8 to 23 C. - Deviation should go a lil bit up and down. - Night and day changes are mild.
                break;
            case 4: // Deserts 
                $tunning = 19; $amplitude = 1; $plus = 0; // 53 to 77 F, 12 to 25 C. - Deviation should go a lil bit up and down. - Night and day changes are HUGE.
                break;
            case 5: // Mountains
                $tunning = 19; $amplitude = 1; $plus = 0; // Same as deserts... For now.
                break;
            case 6: // Swamps
                $tunning = 23; $amplitude = 1.1; $plus = 0; // 15 to 30 C, 59 to 86 F. - Deviation should go a lil bit up and down. - Night and day changes are small.
                break;
            case 7: // Tundra
                $tunning = 1; $amplitude = 2.4; $plus = -2; // -17 to 15 C, 1.4 to 59 F. - Deviation should go up and down. Night and day changes don't exist (it's either always day or night).
                break;
            case 8: // Canyon
                $tunning = 15; $amplitude = 2.7; $plus = 0; // -3 to 32 C, 26.6 to 91.4 F. - Deviation should go a lil bit up and down. - Night and day changes are BIG (down to 10 or even less C).
                break;
            case 9: // Lake
                $tunning = 6.2; $amplitude = 2.4; $plus = 0; // -10 to 22 C, 14 to 71 F. - Deviation should go a somewhat up and down. - Night and day changes are mild.
                break;
            case 10: // Taiga
                $tunning = 1; $amplitude = 1; $plus = 0; // -6 to 7 C, 21 to 44 F. - Deviation should be minimal. - Night and day changes are big, but only in the night's way.
                break;
            case 11: // Tundra (deep)
                $tunning = 1; $amplitude = 2.4; $plus = -12; // -27 to 5 C, -16 to 41 F. - Deviation should be minimal. - Night and day changes don't exist (it's either always day or night).
                break;
        }        
        
        $temperature = (int)($tunning + ($amplitude * $season / $timeDivider) + $plus);

        return $temperature;
        // Most data gathered from https://earthobservatory.nasa.gov/biome/
    }
}

class Location
{
    // - - - ATTRIBUTES
    private int $locationID;    // Discretional
    private int $locationType;  // 1: plains/meadows. 2: jungle. 3: woods/forest. 4: desert. 5: mountains. 6: swamp. 7: tundra. 8: canyon. 9: lake. 10: taiga. 11: tundra (deep)
    private int $weather;       // -1: Sunny. 0: Not raining. 1: Dew. 2: Light rain. 3: rain. 4: downpour. 5: storm.
    private int $clouds;        // Int from 0 to 10.
    private int $humidity;      //
    private int $temperature;   //
    private int $localWater;    //
    

    // - - - CONSTRUCTOR

    public function __construct(int $locationID, int $locationType, int $weather, int $clouds, int $humidity, Int $temperature, int $localWater)
    {
        $this->locationID = $locationID;
        $this->locationType = $locationType;
        $this->weather = $weather;
        $this->clouds = $clouds;
        $this->humidity = $humidity;
        $this->temperature = $temperature;
        $this->localWater = $localWater;
    }

    // - - - PROPERTIES

    public function GetID()
    {
        return $this->locationID;
    }
    public function SetTemperature($temperature)
    {
        $this->temperature = $temperature;
    }
    public function GetTemperature($temperature)
    {
        return $this->temperature;
    }
    public function GetLocationType()
    {
        return $this->locationType;
    }
    public function SetWeather($weather)
    {
        $this->weather = $weather;
    }
    public function GetWeather()
    {
        return $this->weather;
    }


    // - - - MISC
    public function __toString()
    {
        $clouds = $this->TranslateCloudsValue();

        return "Location ID: {$this->locationID}\nSky: {$clouds}";
    }

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

$newLocation = new Location(1, 1, 1, 1, 1, 1, 100);

echo $newLocation;

// - - - - - - - - -








