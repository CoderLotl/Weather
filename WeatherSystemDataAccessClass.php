<?php
class WeatherSystemDataAccess
{    
    private static $hostname;
    private static $username;
    private static $password;
    private static $database;

    public static function SetDBParams(string $hostname, string $username, string $password, string $database)
    {
        WeatherSystemDataAccess::$hostname = $hostname;
        WeatherSystemDataAccess::$username = $username;
        WeatherSystemDataAccess::$password = $password;
        WeatherSystemDataAccess::$database = $database;
    }

    public static function GetDBParams($name)
    {
        switch($name)
        {
            case 'hostname':
                return WeatherSystemDataAccess::$hostname;
            case 'username':
                return WeatherSystemDataAccess::$username;
            case 'password':
                return WeatherSystemDataAccess::$password;
            case 'database':
                return WeatherSystemDataAccess::$database;
            default:
                echo "The attribute doesn't exist.";
                return false;
        }
    }

    public function ReadSeasonDataFromDB(string $table)
    {
        $mysqli = new mysqli($this->hostname, $this->username, $this->password, $this->database);        

        try
        {
            $mysqli->select_db($this->database) or die( "Unable to select database.");
            $data = $mysqli->query("SELECT season_day, season_direction FROM {$table}");

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
                die( "The table is empty." );
            }
            
        }
        catch(Exception $e)
        {
            echo "Imposible to reach the database. Error: " . $e;
        }
        
    }

    public function WriteSeasonDataToDB(SeasonControl $seasonControl, string $table)
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

        $command = "UPDATE {$table} SET season_day = {$day}, season_direction = {$goingForward}";

        $mysqli->query($command);
    }
    
    public function ReadLocationDataFromDB(string $table)
    {
        $locationArray = array();
        $mysqli = new mysqli($this->hostname, $this->username, $this->password, $this->database);

        try
        {
            $mysqli->select_db($this->database) or die( "Unable to select database.");

            $data = $mysqli->query("SELECT id, ". "name" . ", location_type, weather, clouds, water_vapor, temperature, local_water FROM {$table}");

            $mysqli->close();

            if($data->num_rows > 0)
            {
                while($row = $data->fetch_array())
                {            
                    $location = new Location($row['id'], $row['name'], $row['location_type'], $row['weather'], $row['clouds'], $row['water_vapor'], $row['temperature'], $row['local_water']);            
                    array_push($locationArray, $location);
                }
            }
            else
            {
                die( "The table is empty." );
            }

            return $locationArray;
        }
        catch(Exception $e)
        {
            echo "Imposible to reach the database. Error: " . $e;
        }               
    }

    public function WriteLocationDataToDB(Location $location, string $table)
    {
        $mysqli = new mysqli($this->hostname, $this->username, $this->password, $this->database);

        $locationID = $location->__get('id');
        $locationType = $location->__get('type');
        $weather = $location->__get('weather');
        $clouds = $location->__get('clouds');
        $waterVapor = $location->__get('waterVapor');
        $temperature = $location->__get('temperature');
        $localWater = $location->__get('localWater');

        $command = "UPDATE {$table} SET id = {$locationID}, location_type = {$locationType}, weather = {$weather}, clouds = {$clouds}, water_vapor = {$waterVapor}, temperature = {$temperature}, local_water = {$localWater} WHERE id = {$locationID}";
        $mysqli->query($command);
    }
}

?>