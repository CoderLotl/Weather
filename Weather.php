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
The new local water is based on the change of humidity and weather (if the humidity goes down because of condensation, you get more local water.
 If it goes up because of evaporation, you get less local water. - If you get less clouds because of precipitation, you have more water.)

DAY STAGE: ['midnight', 'night', 'dawn', 'morning', 'midday', 'afternoon', 'evening', 'dusk', 'night']

WEATHER STAGES: [-2: Very sunny. -1: Sunny. 0: Not raining. 1: Dew. 2: Light rain. 3: rain. 4: downpour. 5: storm.]

*/
// - - - [ CLASSES ] - - -


class TemperatureParameters
{
    private $tunning;
    private $amplitude;
    private $plus;
    private $topLimits;
    private $bottomLimits;

    public function __construct(float $tunning, float $amplitude, int $plus, array $topLimits, array $bottomLimits)
    {
        $this->tunning = $tunning;
        $this->amplitude = $amplitude;
        $this->plus = $plus;
        $this->topLimits = $topLimits;
        $this->bottomLimits = $bottomLimits;        
    }

    public function GetTunning()
    {
        return $this->tunning;
    }
    public function GetAmplitude()
    {
        return $this->amplitude;
    }
    public function GetPlus()
    {
        return $this->plus;
    }
    public function GetTopLimits($index)
    {
        return $this->topLimits[$index];
    }
    public function GetBottomLimits($index)
    {
        return $this->bottomLimits[$index];
    }

}

class WeatherMachine
{
    public function SetNewTemperature($season, Location $location, $dayStage)
    {        
        $temperature = $this->CalcNewTemperature($season, $location->GetLocationType(), $dayStage, $location->GetWeather());
        $location->SetTemperature($temperature);
        
        echo "Temperature: " . $temperature . "C | " . ((($temperature * 9) / 5) + 32) . "F\n";
    }

    private function ReturnIndexByDayStage(string $dayStage)
    {
        switch($dayStage)
        {
            case 'midnight':
                $indexToReturn = 0;
                break;
            case 'night':
                $indexToReturn = 1;
                break;
            case 'dawn':
                $indexToReturn = 2;
                break;
            case 'morning':
                $indexToReturn = 3;
                break;
            case 'midday':
                $indexToReturn = 4;
                break;
            case 'afternoon':
                $indexToReturn = 5;
                break;
            case 'evening':
                $indexToReturn = 6;
                break;
            case 'dusk':
                $indexToReturn = 7;
                break;
        }

        return $indexToReturn;
    }

    private function SetParamsByLocation($locationType)
    {
        // This part calculates the average temperatures by Location.

        $tunning = 0;       // changing this amplitude affects the top and bottom limits.
                            // Increasing this var moves the range to the positive side. Reducing it does the opposite.
        $amplitude = 0;     // changing this amplitude affects the top and bottom limits.
                            // Increasing this var expands the temp range both ways. Reducing it does the opposite.
        $plus = 0;          // This factor adds a plus.

        // ['midnight', 'night', 'dawn', 'morning', 'midday', 'afternoon', 'evening']

        switch($locationType)
        {
            case 1: // Plains / meadows
                $tunning = 12; $amplitude = 2.6; $plus = 0; // -6 to 29 C, 21 to 85 F. - Deviation should go a lil bit up and down. - Night and day changes are small.
                $topLimits =    [-3, -2, -1, 0, 1, 2, 2, 1];
                $bottomLimits = [-4, -3, -2, -1, 0, 1, 1, 0];
                break;
            case 2: // Jungles 
                $tunning = 23; $amplitude = 0.3; $plus = 0; // 20 to 25 C, 68 to 77 F. - Deviation should only go up. - Night and day changes are small.
                $topLimits =    [-2, -1, 0, 1, 2, 3, 3, 2];
                $bottomLimits = [-3, -2, -1, 0, 1, 2, 2, 1];
                break;
            case 3: // Woods / forests
                $tunning = 11; $amplitude = 2.30; $plus = -3; //  -8 to 23 C, 17 to 73 F. - Deviation should go a lil bit up and down. - Night and day changes are mild.
                $topLimits =    [-3, -2, -1, 0, 1, 2, 2, 1];
                $bottomLimits = [-4, -3, -2, -1, 0, 1, 1, 0];
                break;
            case 4: // Deserts 
                $tunning = 19; $amplitude = 1; $plus = 0; // 53 to 77 F, 12 to 25 C. - Deviation should go a lil bit up and down. - Night and day changes are HUGE.
                $topLimits =    [-10, -7, -4, 0, 7, 10,10, 4];
                $bottomLimits = [-14, -10, -5, 0, 5, 7, 7, 3];
                break;
            case 5: // Mountains
                $tunning = 19; $amplitude = 1; $plus = 0; // Same as deserts... For now.
                $topLimits =    [-10, -7, -4, 0, 7, 10,10, 4];
                $bottomLimits = [-14, -10, -5, 0, 5, 7, 7, 3];
                break;
            case 6: // Swamps
                $tunning = 23; $amplitude = 1.1; $plus = 0; // 15 to 30 C, 59 to 86 F. - Deviation should go a lil bit up and down. - Night and day changes are small.
                $topLimits =    [-3, -2, -1, 0, 1, 2, 2, 1];
                $bottomLimits = [-4, -3, -2, -1, 0, 1, 1, 0];
                break;
            case 7: // Canyon
                $tunning = 15; $amplitude = 2.7; $plus = 0; // -3 to 32 C, 26.6 to 91.4 F. - Deviation should go a lil bit up and down. - Night and day changes are BIG (down to 10 or even less C).
                $topLimits =    [-7, -6, -5, 0, 4, 5, 5, 4];
                $bottomLimits = [-10, -7, -3, 0, 4, 6, 6, 2];
                break;
            case 8: // Lake
                $tunning = 6.2; $amplitude = 2.4; $plus = 0; // -10 to 22 C, 14 to 71 F. - Deviation should go a somewhat up and down. - Night and day changes are mild.
                $topLimits =    [-5, -4, -5, 0, 4, 5, 5, 4];
                $bottomLimits = [-8, -5, -3, 0, 4, 6, 6, 2];
                break;
            case 9: // Taiga
                $tunning = 1; $amplitude = 1; $plus = 0; // -6 to 7 C, 21 to 44 F. - Deviation should be minimal. - Night and day changes are big, but only in the night's way.
                $topLimits =    [-3, -2, -1, 0, 1, 2, 2, 1];
                $bottomLimits = [-4, -3, -2, -1, 0, 1, 1, 0];
                break;
            case 10: // Tundra
                $tunning = 1; $amplitude = 2.4; $plus = -2; // -17 to 15 C, 1.4 to 59 F. - Deviation should go up and down. Night and day changes don't exist (it's either always day or night).
                $topLimits =    [1, 1, 1, 1, 1, 1, 1, 1];
                $bottomLimits = [-6, -4, -3, -1, 0, 1, 1, 0];
                break;
            case 11: // Tundra (deep)
                $tunning = 1; $amplitude = 2.4; $plus = -12; // -27 to 5 C, -16 to 41 F. - Deviation should be minimal. - Night and day changes don't exist (it's either always day or night).
                $topLimits =    [1, 1, 1, 1, 1, 1, 1, 1];
                $bottomLimits = [-6, -4, -3, -1, 0, 1, 1, 0];
                break;
        }

        return new TemperatureParameters($tunning, $amplitude, $plus, $topLimits, $bottomLimits);
    }

    private function CalcNewTemperature(int $season, int $locationType, string $dayStage, $weather)
    {
        // NOTE: Temperatures are in C here. For a F value there needs to be a conversion step.

        $timeDivider = 6; // Since the system has been designed to work with units of 7 days and the seasons have 42 days (6 weeks), this is
                          // an important factor. If the seasons's length ever changes, you can tune it here.
        
        // ------------------------------------------------------ [ INDEX TO USE BY STAGE OF THE DAY ]

        $index = $this->ReturnIndexByDayStage($dayStage);

        // ------------------------------------------------------ [ PARAMS TO USE BY LOCATION ]
                
        $params = $this->SetParamsByLocation($locationType); // In order to see the values of the parameters, check the function.

        $tunning = $params->GetTunning(); $amplitude = $params->GetAmplitude(); $plus = $params->GetAmplitude();
        $topLimits = $params->GetTopLimits($index); $bottomLimits = $params->GetBottomLimits($index);
        
        // ------------------------------------------------------ [ WEATHER EFFECT'S PARAM ]

        $weatherEffect = $this->SetTempReductionParameterByWeather($weather);

        // ------------------------------------------------------ [ CALCULATIONS ]
        
        $averageTemperature = (int)($tunning + ($amplitude * $season / $timeDivider) + $plus);

        $temperature = rand( ( $averageTemperature + $bottomLimits ), ( $averageTemperature + $topLimits ) );

        $temperature = ( $temperature + ( ( $temperature * $weatherEffect ) / 100 ) );

        return $temperature;
        // Most data gathered from https://earthobservatory.nasa.gov/biome/
    }

    private function SetTempReductionParameterByWeather($weather)
    {
        switch($weather)
        {
            // WEATHER STAGES: [-2: Very sunny. -1: Sunny. 0: Not raining. 1: Dew. 2: Light rain. 3: rain. 4: downpour. 5: storm.]
            case -2:
                $param = 0;
                break;
            case -1:
                $param = 0;
                break;
            case 0:
                $param = 0;
                break;
            case 1:
                $param = 0;
                break;
            case 2:
                $param = 0;
                break;
            case 3:
                $param = 0;
                break;
            case 4:
                $param = 0;
                break;
            case 5:
                $param = 0;
                break;
        }

        return $param;
    }
}

class Location
{
    // - - - ATTRIBUTES
    private $locationID;    // Discretional
    private $locationType;  // 1: plains/meadows. 2: jungle. 3: woods/forest. 4: desert. 5: mountains. 6: swamp. 7: canyon. 8: lake. 9: taiga. 10: tundra. 11: tundra (deep)
    private $weather;       // -1: Sunny. 0: Not raining. 1: Dew. 2: Light rain. 3: rain. 4: downpour. 5: storm.
    private $clouds;        // Int from 0 to 10.
    private $humidity;      //
    private $temperature;   //
    private $localWater;    //
    

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

        return "Location ID: {$this->locationID}\nSky: {$clouds}\nTemperature: {$this->temperature}";
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

// - - - - - - - - - - -
// - - - [ TEST ] - - - * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
// - - - - - - - - - - -

$newLocation = new Location(1, 1, 1, 1, 1, 1, 100);
$weatherMachine = new WeatherMachine();

$weatherMachine->SetNewTemperature(-13,$newLocation,'midday');
$weatherMachine->SetNewTemperature(-13,$newLocation,'midday');
$weatherMachine->SetNewTemperature(-13,$newLocation,'midday');

echo $newLocation;

// - - - - - - - - -





