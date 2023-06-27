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

$option = 1; // 1 = SQLITE - - - 2 = MYSQL
$days = 50; // Amount of days to run
$weatherMachine = new WeatherMachine();
$locationArray = '';
$seasonControl = '';
$dataAccess = '';
$clock = '';

switch($option)
{
    case 1: // SQLITE
        WeatherSystemSQLiteDataAccess::SetDBPath("WeatherTest.db"); // Setting the database we're gonna work with.
        $dataAccess = new WeatherSystemSQLiteDataAccess();        
        break;
    case 2: // MYSQL
        WeatherSystemDataAccess::SetDBParams('localhost:3306', 'root', '' ,'weather_test');
        $dataAccess = new WeatherSystemDataAccess();                
        break;
}

$seasonControl = $dataAccess->ReadSeasonDataFromDB("worlds");

$day = ['midnight', 'night', 'dawn', 'morning', 'midday', 'afternoon', 'evening', 'dusk', 'night'];
//$day = ['midday'];

if($dataAccess::GetDBParams('histoical') === true)
{
    $locationArray = $dataAccess->ReadLocationDataFromDB('locs', 2);
}
else
{
    $locationArray = $dataAccess->ReadLocationDataFromDB("locs");
}


for($i = 0; $i < $days; $i ++)
{
    foreach($day as $dayStage)
    {
        echo "\n*** LOCATION BLOCK ***";
        echo "\nDay Stage: {$dayStage}";
     
        echo "\nLocations:\n\n";        
        
        foreach($locationArray as $newLocation)
        {
            if($weatherMachine->ExecuteWeatherTick($seasonControl->GetDay(), $newLocation, $dayStage) == true)
            {                
                echo $newLocation . "\n-------\n";

                $totalLiquids = ($newLocation->__get('clouds') + $newLocation->__get('localWater') + $newLocation->__get('waterVapor'));
                echo "\nTOTAL LIQUIDS: {$totalLiquids}\n\n";
            }            
        }          
    }
    $seasonControl->Tick();
}

$dataAccess->UpdateSeasonDataToDB($seasonControl, 'worlds');
$dataAccess->UpdateAllLocationsAtDB($locationArray, 'locs');
if(WeatherSystemDataAccess::GetDBParams('historical') === true)
{
    $weatherSystemdataAccess->WriteLocationsToDB($locationArray, 'test');
}