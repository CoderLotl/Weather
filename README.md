---

# - - - [ DESCRIPTION ]

The Weather System is a generic and rather simple back-end mechanism developed for the game **URPG** from [Tinydark Studio](https://tinydark.com/).
It has been designed under the specifications of **Vael Victus**, URPG's owner, and granted the status of *free to use* code.
It's purpose is to simulate weather changes through a custom year cycle for the game's locations, generating a rather complex climate and the sensation of Seasons.
The system simulates temperature changes and variations, and water cycles (evaporation, cloudification, and precipitation).

The system is programmed in OOP and is designed to work with SQLite and MySQL. It has also been designed to work with seasons of 21 days (3 weeks), with 1 year-cycle being 84 days.
Cycles can be modified through code manipulation though, which makes the system flexible and adaptable for different contexts.

The mechanism is compossed by the following

### Classes:
- **Location**: used to create a representation of the location to iterate with all the relevant data of the location.
It counts with the relevant public functions **_ToString()** which returns all the data of the location, **TranslateWeather()** which returns a string representing the current weather, and
**TranslateCloudsValue()** which returns a string representing the current clouds.
  
- **Season Control**: the system's inner clock. It moves through a numeric scope which goes from the minimum day to the maximum day, ideally -42 to 42, and then back again.
It also keeps the registry if it's going towards positive or negative numbers. With the value of the day and direction it can return the value of the current season.
This class is consumed by the **Weather Machine** in order to know the current season and do the weather calculations.

- **Weather Machine**: the system's core. This class contains all the relevant functions which do the weather calculations. It also provides some public functions to calculate
the current saturation point for a given humidity and the current relative humidity given the temperature and humidity.
This class works with a big set of constant parameters which can be manually tweaked. It's also prepared to work with 8 different day stages which are *'midnight', 'night', 'dawn', 'morning', 'midday', 'afternoon', 'evening', 'dusk', 'night'*. The different values of temperature for the stages are located at the function **SetParamsByLocation()**.

- **Temperature Parameters**: this is a *package class*, instantiated and consumed inside the **Weather Machine** for moving parameters from one function to another.

### Services:
- **Data Access MySQL / SQLite**: two different classes with the exact same purpose, designed to work with MySQL or SQLite. Given the path to the database and credentials if needed
they can create the required tables for the system to work.


---

# - - - [ DOCUMENTATION ]


The weather system is controlled by the class WeatherMachine, which has no constructor and only 1 public function.

The calculation of weather requires of several external variables:

- SEASON: a number that goes from -42 to 42. This range can be changed, but requires a proper adjustment of the system's maths, which is explained here.
- DAY STAGE: a string. The system currently has 8 day stages. The amount of stages can be set higher or lower, but requires a proper adjustment of a set of arrays, together with a switch.
- LOCATION: the location structure is given with this system.

## [ INSIDE WORK OF THE WEATHER MACHINE ]

### *TEMPERATURE, controlled by the class WeatherMachine*

The temperature depends on 3 main variables: the season factor, the stage of the day, and the type of location.
The equations which determine the temperature, at first, are these:

        $averageTemperature = (int)($tuning + ($amplitude * $season / $timeDivider) + $plus);

        $temperature = rand( ( $averageTemperature + $bottomLimits ), ( $averageTemperature + $topLimits ) );

        $temperature = ( $temperature + ( ( $temperature * $weatherEffect ) / 100 ) );


To understand this, we have to take the 1st equation of the 3, and keep in mind that the equation is a function in the mathematical sense.

**[ 1st EQUATION ]**

The *AMPLITUDE* defines the width of the numerical domain of our function. The *TUNING* defines how much to the left (the negative numbers area) or to the right (the positives) the domain is.

The *TIME DIVIDER* is a variable that's defined by the length of units each season lasts.
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



