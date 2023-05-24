<?php
require('WeatherSystemSQLiteDataAccess.php');
require('WeatherSystemDataAccessClass.php');
require('TemperatureParametersClass.php');
require('SeasonControlClass.php');
require('WeatherMachineClass.php');
require('LocationClass.php');

//////////////////////////
/* NOTES */
{
    /*
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
}


// - - - - - - - - - - -
// - - - [ TEST ] - - - * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
// - - - - - - - - - - -

$weatherMachine = new WeatherMachine();

//$weatherSystemdataAccess = new WeatherSystemDataAccess("localhost:3306","weather_test","weather123","weathertest");
//$seasonControl = $weatherSystemdataAccess->ReadSeasonDataFromDB("worlds");

WeatherSystemSQLiteDataAccess::SetDBPath("WeatherTest.db"); // Setting the database we're gonna work with.
$weatherSystemDataAccessSQLite = new WeatherSystemSQLiteDataAccess();
$seasonControl = $weatherSystemDataAccessSQLite->ReadSeasonDataFromDB("worlds"); // Creating the Season Control object, passing the table name the Control is going to work with.

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
