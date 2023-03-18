<?php
class WeatherSystemDataAccess
{    
    private $hostname;
    private $username;
    private $password;
    private $database;

    public function __construct(string $hostname, string $username, string $password, string $database)
    {
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    public function GetHostname()
    {
        return $this->hostname;
    }
    public function GetUsername()
    {
        return $this->username;
    }
    public function GetPassword()
    {
        return $this->password;
    }
    public function GetDatabase()
    {
        return $this->database;
    }

    public function ReadSeasonDataFromDB()
    {
        $mysqli = new mysqli($this->hostname, $this->username, $this->password, $this->database);        

        try
        {
            $mysqli->select_db($this->database) or die( "Unable to select database");
            $data = $mysqli->query('SELECT season_day, season_direction FROM worlds');

            $mysqli->close();

            if($data->num_rows > 0)
            {
                $data = $data->fetch_array();

                $seasonControl = new SeasonControl();
                $seasonControl->SetDay($data[0]);
                if($data[1] == 0)
                {
                    $seasonControl->SetGoingForward(false);            
                }
                else
                {
                    $seasonControl->SetGoingForward(true);
                }

                echo "\nSeaconControl created successfully.";
                
                return $seasonControl;
            }
            else
            {
                die( "The table is empty" );
            }
            
        }
        catch(Exception $e)
        {
            echo "Imposible to reach the database. Error: " . $e;
        }
        
    }

    public function WriteSeasonDataToDB(SeasonControl $seasonControl)
    {
        $mysqli = new mysqli($this->hostname, $this->username, $this->password, $this->database);

        $day = $seasonControl->GetDay();
        if($seasonControl->GetGoingForward() == false)
        {
            $goingForward = 0;
        }
        else
        {
            $goingForward = 1;
        }        

        $command = "UPDATE worlds SET season_day = {$day}, season_direction = {$goingForward}";

        $mysqli->query($command);
    }
    
    public function ReadLocationDataFromDB()
    {
        $locationArray = array();
        $mysqli = new mysqli($this->hostname, $this->username, $this->password, $this->database);

        try
        {
            $mysqli->select_db($this->database) or die( "Unable to select database");

            $data = $mysqli->query('SELECT location_id, location_type, weather, clouds, water_vapor, temperature, local_water FROM locations');

            $mysqli->close();

            if($data->num_rows > 0)
            {
                while($row = $data->fetch_array())
                {            
                    $location = new Location($row['location_id'], $row['location_type'], $row['weather'], $row['clouds'], $row['water_vapor'], $row['temperature'], $row['local_water']);            
                    array_push($locationArray, $location);
                }
            }
            else
            {
                die( "The table is empty" );
            }

            return $locationArray;
        }
        catch(Exception $e)
        {
            echo "Imposible to reach the database. Error: " . $e;
        }               
    }

    public function WriteLocationDataToDB(Location $location)
    {
        $mysqli = new mysqli($this->hostname, $this->username, $this->password, $this->database);

        $locationID = $location->GetID();
        $locationType = $location->GetLocationType();
        $weather = $location->GetWeather();
        $clouds = $location->GetClouds();
        $waterVapor = $location->GetWaterVapor();
        $temperature = $location->GetTemperature();
        $localWater = $location->GetLocalWater();

        $command = "UPDATE locations SET location_id = {$locationID}, location_type = {$locationType}, weather = {$weather}, clouds = {$clouds}, water_vapor = {$waterVapor}, temperature = {$temperature}, local_water = {$localWater} WHERE location_id = {$locationID}";
        $mysqli->query($command);
    }
}

?>