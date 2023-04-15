<?php
require('WeatherSystemSQLiteDataAccess.php');
require('TemperatureParametersClass.php');
require('SeasonControlClass.php');
require('WeatherSystemDataAccessClass.php');
require('WeatherMachineClass.php');
require('LocationClass.php');

/*

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
SEASON: it could be just a number that drifts from -10 to 10, instead of a rough change. NOTE: it actually drifts from -42 to 42. (!!!)
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

0. - Now the weather starts as clear, the temperature as an average point for a season = 0, the same with humidity (except you want some specific value).
     The local water starts at whatever you want.

1. - Based on that, the new temperature is set depending on the previous weather and season, and location type.

2. - The new weather is set based on the humidity and clouds and new temperature.

3. - The new humidity is based on the new temperature and weather.

4. - The new local water is based on the change of humidity and weather (if the humidity goes down because of condensation, you get more local water.

5. - If it goes up because of evaporation, you get less local water. - If you get less clouds because of precipitation, you have more water.)

DAY STAGE: ['midnight', 'night', 'dawn', 'morning', 'midday', 'afternoon', 'evening', 'dusk', 'night']
WEATHER STAGES: [ 0: Not raining. 1: Dew. 2: Drizzle. 3: Light rain. 4: rain. 5: downpour. 6: storm.]


WATER:

15 -> DESERT
15 -> CANYON
50 -> MOUNTAINS
30 -> TUNDRA
100 -> PLAINS / MEADOWS / TAIGA
200 -> WOODS / FOREST
1000 -> SWAMPS / LAKE
250 -> JUNGLES

*/



// - - - - - - - - - - -
// - - - [ TEST ] - - - * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
// - - - - - - - - - - -

$weatherMachine = new WeatherMachine();
//$weatherSystemdataAccess = new WeatherSystemDataAccess("localhost:3306","weather_test","weather123","weathertest");
$weatherSystemDataAccessSQLite = new WeatherSystemSQLiteDataAccess("WeatherTest.db");
//$seasonControl = $weatherSystemdataAccess->ReadSeasonDataFromDB("worlds");
$seasonControl = $weatherSystemDataAccessSQLite->ReadSeasonDataFromDB("worlds");

$day = ['midnight', 'night', 'dawn', 'morning', 'midday', 'afternoon', 'evening', 'dusk', 'night'];

for($i = 0; $i < 1; $i ++)
{
    foreach($day as $dayStage)
    {
        echo "\n*** LOCATION BLOCK ***";
        echo "\nDay Stage: {$dayStage}";
        //$locationArray = $weatherSystemdataAccess->ReadLocationDataFromDB("locs");
        $locationArray = $weatherSystemDataAccessSQLite->ReadLocationDataFromDB("locs");
        echo "\nLocations:\n\n";
        foreach($locationArray as $newLocation)
        {
            //if($weatherMachine->ExecuteWeatherTick($seasonControl->GetDay(), $newLocation, $dayStage, $weatherSystemdataAccess, "locs") == true)
            if($weatherMachine->ExecuteWeatherTickSQLite($seasonControl->GetDay(), $newLocation, $dayStage, $weatherSystemDataAccessSQLite, "locs") == true)
            {
                echo $newLocation . "\n-------\n";
            }        
        }
    }
}
// - - - - - - - - -

// - - - [ DOCUMENTATION ] - - -
/*

The weather system is controlled by the class WeatherMachine, which has no constructor and only 1 public function.

The calculation of weather requires of several external variables:

- SEASON: a number that goes from -42 to 42. This range can be changed, but requires a proper adjustment of the system's maths, which is explained here.
- DAY STAGE: a string. The system currently has 8 day stages. The amount of stages can be set higher or lower, but requires a proper adjustment of a set of arrays, together with a switch.
- LOCATION: the location structure is given with this system.




[ INSIDE WORK OF THE WEATHER MACHINE]

TEMPERATURE, controlled by the class WeatherMachine

The temperature depends on 3 main variables: the season factor, the stage of the day, and the type of location.
The equations that determine the temperature, at first, are these:

        $averageTemperature = (int)($tuning + ($amplitude * $season / $timeDivider) + $plus);

        $temperature = rand( ( $averageTemperature + $bottomLimits ), ( $averageTemperature + $topLimits ) );

        $temperature = ( $temperature + ( ( $temperature * $weatherEffect ) / 100 ) );


To understand this, we have to take the 1st equation of the 3, and keep in mind that the equation is a function in the mathematical sense.

[ 1st EQUATION ]

The AMPLITUDE will define the width of the numerical domain of our function. The TUNING will define how much to the left (the negative numbers area) or to the right (the positives) the domain is.

The TIME DIVIDER is a variable that's defined by the length of units each season lasts.
The original system was developed for a system of 7 days per season. Since the seasons were later extended to 42 days each (that's 6 weeks. 7 * 6), the only way to adjust the maths back to
the original design was to define the season length at 42, and add the $timeDivider, setting it to 6. - If you ever want to adapt this system to a different length for the seasons, keep this in mind.

The SEASON is the absolute domain of numbers. That means: it's the total range the equation is going to take as max and min numbers.
Originally it was of 7 days per season, so the range for SEASON used to go from -7, to 7 (from cold season to warm season). Now it's of -42 to 42.
If you want to adapt the system to a different range of time per season, keep in mind that this has to keep some relation with the $timeDivider.

The PLUS variable is used discretionally and for correcting the numbers manually in a desired way whenever the need arises. Normally its value is 0, but you can set it to whatever.

With all this, the 1st equation returns the $averageTemperature.

- - -

[ 2nd EQUATION ]

The 2nd equation corresponds to the deviation of temperature given the DAY STAGE and the LOCATION TYPE.
The LOCATION TYPE would switch between different arrays of numbers (one for the bottom limit of the deviation, one for the top limit) that are the collections of deviations, at reason of one per DAY STAGE.
The DAY STAGE would indicate the index of such arrays.

So the $temperature would be a random number between ( $averageTemperature + $bottomLimits ) and ( $averageTemperature + $topLimits ).
Both $bottomLimits and $topLimits are set by GetTopLimits($index) and GetBottomLimits($index), both functions from the class TemperatureParameters, which return the value of the array at the specified index.

The INDEX, by the way, is obtained by the function ReturnIndexByDayStage($dayStage), which returns the index given the DAY STAGE (a string).

- - -

[ 3rd EQUATION ]

The final equation is simply summing the temperature with a modifyer, which may be either positive or negative, and it's a percentage of the current temperature set by the current weather.


*/

