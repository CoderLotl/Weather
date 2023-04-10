<?php

class WeatherSystemSQLiteDataAccess
{
    private $DBPath;

    public function __construct(string $DBPath)
    {
        $this->DBPath = $DBPath;
    }

    public function GetDBPath()
    {
        return $this->DBPath;
    }

    public function ReadSeasonDataFromDB(string $table)
    {
        $seasonControl = new SeasonControl();

        $db = new SQLite3($this->DBPath);

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
        $db = new SQLite3($this->DBPath);

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
        $db = new SQLite3($this->DBPath);

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
        $db = new SQLite3($this->DBPath);

        $locationID = $location->GetID();
        $locationType = $location->GetLocationType();
        $weather = $location->GetWeather();
        $clouds = $location->GetClouds();
        $waterVapor = $location->GetWaterVapor();
        $temperature = $location->GetTemperature();
        $localWater = $location->GetLocalWater();

        $command = "UPDATE {$table} SET location_id = {$locationID}, location_type = {$locationType}, weather = {$weather}, clouds = {$clouds}, water_vapor = {$waterVapor}, temperature = {$temperature}, local_water = {$localWater} WHERE location_id = {$locationID}";
        $db->query($command);
    }
}

?>