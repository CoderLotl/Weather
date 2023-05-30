<?php

/**
 * Connects with a given SQLite DB where the tables where the season and locations are stored.
 * Provides public functions to read and write both season's and location's data.
 */
class WeatherSystemSQLiteDataAccess
{
    private static $DBPath;

    public static function SetDBPath(string $dbPath)
    {
        WeatherSystemSQLiteDataAccess::$DBPath = $dbPath;
    }

    public static function GetDBPath()
    {
        return WeatherSystemSQLiteDataAccess::$DBPath;
    }

    public function ReadSeasonDataFromDB(string $table)
    {
        $seasonControl = new SeasonControl();

        $db = new SQLite3(WeatherSystemSQLiteDataAccess::$DBPath);

        $data = $db->query("SELECT season_day, season_direction FROM {$table}");
        $data = $data->fetchArray();        

        $seasonControl->SetDay($data["season_day"]);

        if($data["season_direction"] == 0)
        {
            $seasonControl->SetGoingForward(false);
        }
        else
        {
            $seasonControl->SetGoingForward(true);
        }
        return $seasonControl;
    }

    public function WriteSeasonDataToDB(SeasonControl $seasonControl, string $table)
    {
        $db = new SQLite3(WeatherSystemSQLiteDataAccess::$DBPath);

        $day = $seasonControl->GetDay();        
        if($seasonControl->GetGoingForward() == false)
        {
            $goingForward = 0;
        }
        else
        {
            $goingForward = 1;
        }        

        $command = "UPDATE {$table} SET season_day = {$day}, season_direction = {$goingForward}";

        $db->query($command);
    }

    public function ReadLocationDataFromDB(string $table)
    {        
        $locationArray = array();
        $db = new SQLite3(WeatherSystemSQLiteDataAccess::$DBPath);

        $data = $db->query("SELECT * FROM {$table}");

        while($row = $data->fetchArray())
        {
            $location = new Location($row['location_id'], $row['location_name'], $row['location_type'], $row['weather'], $row['clouds'], $row['water_vapor'], $row['temperature'], $row['local_water']);            
            array_push($locationArray, $location);            
        }

        return $locationArray;        
    }

    public function WriteLocationDataToDB(Location $location, string $table)
    {
        $db = new SQLite3(WeatherSystemSQLiteDataAccess::$DBPath);

        $locationID = $location->__get("id");        
        $weather = $location->__get("weather");
        $clouds = $location->__get("clouds");
        $waterVapor = $location->__get("waterVapor");
        $temperature = $location->__get("temperature");
        $localWater = $location->__get("localWater");        

        $command = "UPDATE {$table} SET weather = {$weather}, clouds = {$clouds}, water_vapor = {$waterVapor}, temperature = {$temperature}, local_water = {$localWater} WHERE location_id = {$locationID};";
        $db->query($command);
        $db->close();
    }

    public function UpdateAllLocationsDataToDB($locations, string $table)
    {
        $db = new SQLite3(WeatherSystemSQLiteDataAccess::$DBPath);
        $command = '';

        foreach($locations as $location)
        {
            $locationID = $location->__get('id');            
            $weather = $location->__get('weather');
            $clouds = $location->__get('clouds');
            $waterVapor = $location->__get('waterVapor');
            $temperature = $location->__get('temperature');
            $localWater = $location->__get('localWater');

            $command .= "UPDATE {$table} SET weather = {$weather}, clouds = {$clouds}, water_vapor = {$waterVapor}, temperature = {$temperature}, local_water = {$localWater} WHERE location_id = {$locationID};";
        }
        $db->query($command);
        $db->close();
    }
}

?>