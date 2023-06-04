<?php
class WeatherSystemDataAccess
{    
    private static $hostname;
    private static $username;
    private static $password;
    private static $database;
    private static $historical;    

    public static function SetDBParams(string $hostname, string $username = null, string $password = null, string $database, bool $historical = false)
    {
        self::$hostname = $hostname;
        self::$username = $username;
        self::$password = $password;        
        self::$database = $database;        
        self::$historical = $historical;
    }

    public static function GetDBParams($name)
    {
        switch($name)
        {
            case 'hostname':
                return self::$hostname;
            case 'username':
                return self::$username;
            case 'password':
                return self::$password;
            case 'database':
                return self::$database;
            case 'historical':
                return self::$historical;
            default:
                echo "The attribute doesn't exist.";
                return false;
        }
    }

    public function CreateTables()
    {
        $mysqli = new mysqli(self::$hostname, self::$username, self::$password, self::$database);
        $worldsTable = "CREATE TABLE IF NOT EXISTS `worlds` (`season_day` int(11) NOT NULL,`season_direction` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        $locsTable = "CREATE TABLE IF NOT EXISTS `weather_test`.`locs` (`location_id` INT NOT NULL , `location_name` TEXT NOT NULL , `location_type` INT NOT NULL , `weather` INT NOT NULL , `clouds` FLOAT NOT NULL , `water_vapor` FLOAT NOT NULL , `temperature` INT NOT NULL , `local_water` FLOAT NOT NULL , `timestamp_id` INT NOT NULL) ENGINE = InnoDB;";

        $mysqli->query($worldsTable);
        $mysqli->query($locsTable);
        $mysqli->close();
    }
    #region SEASON
    public function ReadSeasonDataFromDB(string $table)
    {
        $mysqli = new mysqli(self::$hostname, self::$username, self::$password, self::$database);
        try
        {
            $mysqli->select_db(self::$database) or die( "Unable to select database.");
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
        $mysqli = new mysqli(self::$hostname, self::$username, self::$password, self::$database);

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
    #endregion
    #region LOCATIONS
    public function ReadLocationDataFromDB(string $table, $limit = null)
    {
        $locationArray = [];
        $dsn = "mysql:host=" . self::$hostname . ";dbname=" . self::$database;
        $pdo = new PDO($dsn, self::$username, self::$password);        

        try
        {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);            
            $query = '';
            if(self::$historical === true && $limit === null)
            {
                die("ERROR. The retrieve is historical but the LIMIT param is null.");
            }
            elseif(self::$historical === true && $limit !== null)
            {
                $query = "SELECT location_id, location_name, location_type, weather, clouds, water_vapor, temperature, local_water FROM {$table} ORDER BY timestamp_id DESC LIMIT {$limit}";
            }
            else
            {
                $query = "SELECT location_id, location_name, location_type, weather, clouds, water_vapor, temperature, local_water FROM {$table}";
            }

            $statement = $pdo->prepare($query);            
            $statement->execute();
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    
            if (count($result) > 0)
            {
                foreach ($result as $row)
                {
                    $location = new Location($row['location_id'], $row['location_name'], $row['location_type'], $row['weather'], $row['clouds'], $row['water_vapor'], $row['temperature'], $row['local_water']);
                    array_push($locationArray, $location);
                }
            }
            else
            {
                die("The table is empty.");
            }

            return $locationArray;
        }
        catch (PDOException $e)
        {
            die("Impossible to reach the database. Error: " . $e->getMessage());
        }               
    }

    public function WriteLocationsToDB($locations, string $table)
    {        
        if(self::$historical === true)
        {
            $mysqli = new mysqli(self::$hostname, self::$username, self::$password, self::$database);
            $command = '';
            $timestamp = time();
    
            foreach($locations as $location)
            {
                $locationID = $mysqli->real_escape_string($location->__get('id'));
                $locationType = $mysqli->real_escape_string($location->__get('type'));
                $locationName = $mysqli->real_escape_string($location->__get('name'));
                $weather = $mysqli->real_escape_string($location->__get('weather'));
                $clouds = $mysqli->real_escape_string($location->__get('clouds'));
                $waterVapor = $mysqli->real_escape_string($location->__get('waterVapor'));
                $temperature = $mysqli->real_escape_string($location->__get('temperature'));
                $localWater = $mysqli->real_escape_string($location->__get('localWater'));
    
                $command .= "INSERT INTO {$table} (location_id, location_type, location_name, weather, clouds, water_vapor, temperature, local_water, timestamp_id) VALUES ('$locationID', '$locationType', '$locationName', '$weather', '$clouds', '$waterVapor', '$temperature', '$localWater', '$timestamp');";
            }      
            $mysqli->multi_query($command);
            $mysqli->close();
            return true;
        }
        else
        {
            echo "\nERROR. The current instance isn't set as Historical.\n";
            return false;
        }
    }

    public function UpdateLocationAtDB(Location $location, string $table)
    {
        $mysqli = new mysqli(self::$hostname, self::$username, self::$password, self::$database);

        $locationID = $location->__get('id');        
        $weather = $location->__get('weather');
        $clouds = $location->__get('clouds');
        $waterVapor = $location->__get('waterVapor');
        $temperature = $location->__get('temperature');
        $localWater = $location->__get('localWater');

        $command = "UPDATE {$table} SET weather = {$weather}, clouds = {$clouds}, water_vapor = {$waterVapor}, temperature = {$temperature}, local_water = {$localWater} WHERE location_id = {$locationID};";
        $mysqli->query($command);
        $mysqli->close();
    }

    public function UpdateAllLocationsAtDB($locations, string $table)
    {
        $mysqli = new mysqli(self::$hostname, self::$username, self::$password, self::$database);
        $command = null;
    
        foreach($locations as $location)
        {
            $locationID = $mysqli->real_escape_string($location->__get('id'));
            $weather = $mysqli->real_escape_string($location->__get('weather'));
            $clouds = $mysqli->real_escape_string($location->__get('clouds'));
            $waterVapor = $mysqli->real_escape_string($location->__get('waterVapor'));
            $temperature = $mysqli->real_escape_string($location->__get('temperature'));
            $localWater = $mysqli->real_escape_string($location->__get('localWater'));
    
            $command .= "UPDATE {$table} SET weather = '{$weather}', clouds = '{$clouds}', water_vapor = '{$waterVapor}', temperature = '{$temperature}', local_water = '{$localWater}' WHERE location_id = '{$locationID}';";
        }      
        $mysqli->multi_query($command);
        $mysqli->close();
    }
    #endregion
}

?>