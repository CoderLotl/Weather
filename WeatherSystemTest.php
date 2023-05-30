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

//$weatherSystemdataAccess = new WeatherSystemDataAccess("localhost:3306","weather_test","weather123","weathertest");
//$seasonControl = $weatherSystemdataAccess->ReadSeasonDataFromDB("worlds");

$option = 2; // 1 = SQLITE - - - 2 = MYSQL

$weatherMachine = new WeatherMachine();
$locationArray = '';
$seasonControl = '';
$weatherSystemDataAccessSQLite = '';
$weatherSystemdataAccess = '';

switch($option)
{
    case 1: // SQLITE
        WeatherSystemSQLiteDataAccess::SetDBPath("WeatherTest.db"); // Setting the database we're gonna work with.
        $weatherSystemDataAccessSQLite = new WeatherSystemSQLiteDataAccess();
        $seasonControl = $weatherSystemDataAccessSQLite->ReadSeasonDataFromDB("worlds"); // Creating the Season Control object, passing the table name the Control is going to work with.
        break;
    case 2: // MYSQL
        WeatherSystemDataAccess::SetDBParams('localhost:3306', 'root', '' ,'weather_test', true);
        $weatherSystemdataAccess = new WeatherSystemDataAccess();
        $seasonControl = $weatherSystemdataAccess->ReadSeasonDataFromDB("worlds");
        break;
}


$day = ['midnight', 'night', 'dawn', 'morning', 'midday', 'afternoon', 'evening', 'dusk', 'night'];
//$day = ['midday'];

switch($option)
{
    case 1: // SQLITE
        if(WeatherSystemSQLiteDataAccess::GetDBParams('historical') == true)
        {            
            $locationArray = $weatherSystemdataAccess->ReadLocationDataFromDB('locs', 2);
        }
        else
        {
            $locationArray = $weatherSystemDataAccessSQLite->ReadLocationDataFromDB("locs");
        }
        break;
    case 2: // MYSQL
        if(WeatherSystemDataAccess::GetDBParams('historical') == true)
        {            
            $locationArray = $weatherSystemdataAccess->ReadLocationDataFromDB('locs', 2);
        }
        else
        {
            $locationArray = $weatherSystemDataAccessSQLite->ReadLocationDataFromDB("locs");
        }
        break;
}   

var_dump($locationArray);

for($i = 0; $i < 1; $i ++)
{
    foreach($day as $dayStage)
    {
        echo "\n*** LOCATION BLOCK ***";
        echo "\nDay Stage: {$dayStage}";
     
        echo "\nLocations:\n\n";
        //var_dump($locationArray);
        
        foreach($locationArray as $newLocation)
        {
            if($option === 2)
            {
                if($weatherMachine->ExecuteWeatherTick($seasonControl->GetDay(), $newLocation, $dayStage) == true)
                {
                    //$weatherSystemdataAccess->UpdateLocationAtDB($newLocation, 'locs');
                    echo $newLocation . "\n-------\n";

                    $totalLiquids = ($newLocation->__get('clouds') + $newLocation->__get('localWater') + $newLocation->__get('waterVapor'));
                    echo "\nTOTAL LIQUIDS: {$totalLiquids}\n\n";
                }
            }
            else
            {
                if($weatherMachine->ExecuteWeatherTickSQLite($seasonControl->GetDay(), $newLocation, $dayStage) == true)
                {
                    //$weatherSystemDataAccessSQLite->WriteLocationDataToDB($newLocation, 'locs');
                    echo $newLocation . "\n-------\n";
    
                    $totalLiquids = ($newLocation->__get('clouds') + $newLocation->__get('localWater') + $newLocation->__get('waterVapor'));
                    echo "\nTOTAL LIQUIDS: {$totalLiquids}\n\n";
                }       
            }            
        }          
    }
}

if($option === 2)
{
    $weatherSystemdataAccess->UpdateAllLocationsAtDB($locationArray, 'locs');
    $weatherSystemdataAccess->WriteLocationsToDB($locationArray, 'test');
}
else
{
    $weatherSystemDataAccessSQLite->UpdateAllLocationsDataToDB($locationArray, 'locs');
}