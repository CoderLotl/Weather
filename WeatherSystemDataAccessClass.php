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
        $dsn = "mysql:host=" . self::$hostname . ";dbname=" . self::$database;
        $pdo = new PDO($dsn, self::$username, self::$password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try
        {
            $worldsTable = "CREATE TABLE IF NOT EXISTS `worlds` (`season_day` int(11) NOT NULL,`season_direction` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            $locsTable = "CREATE TABLE IF NOT EXISTS `weather_test`.`locs` (`location_id` INT NOT NULL , `location_name` TEXT NOT NULL , `location_type` INT NOT NULL , `weather` INT NOT NULL , `clouds` FLOAT NOT NULL , `water_vapor` FLOAT NOT NULL , `temperature` INT NOT NULL , `local_water` FLOAT NOT NULL , `timestamp_id` INT NOT NULL) ENGINE = InnoDB;";
    
            $statement = $pdo->prepare($worldsTable);            
            $statement->execute();
            $statement = $pdo->prepare($locsTable);            
            $statement->execute();                        
            return true;
        }
        catch(PDOException $e)
        {
            echo $e;
            return false;
        }
    }
    #region SEASON
    public function ReadSeasonDataFromDB(string $table)
    {
        $dsn = "mysql:host=" . self::$hostname . ";dbname=" . self::$database;
        $pdo = new PDO($dsn, self::$username, self::$password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try
        {  
            $statement = $pdo->prepare("SELECT season_day, season_direction FROM {$table}");
            $statement->execute();
            $data = $statement->fetch(PDO::FETCH_ASSOC);

            if(count($data) > 0)
            {                
                $seasonControl = new SeasonControl();
                $seasonControl->SetDay($data['season_day']);

                if($data['season_direction'] == 0)
                {
                    $seasonControl->SetGoingForward(false);            
                }
                else
                {
                    $seasonControl->SetGoingForward(true);
                }

                echo "\nSeaconControl created successfully.\n";
                
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

    public function UpdateSeasonDataToDB(SeasonControl $seasonControl, string $table)
    {
        $dsn = "mysql:host=" . self::$hostname . ";dbname=" . self::$database;
        $pdo = new PDO($dsn, self::$username, self::$password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

        try
        {
            $day = $seasonControl->GetDay();
            if($seasonControl->GetGoingForward() == false)
            {
                $goingForward = 0;
            }
            else
            {
                $goingForward = 1;
            }        
    
            $query = "UPDATE {$table} SET season_day = {$day}, season_direction = {$goingForward}";
    
            $statement = $pdo->prepare($query);            
            $statement->execute();
            return true;
        }
        catch(PDOException $e)
        {
            echo $e;
            return false;
        }
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
                echo "The table is empty.";
                return false;
            }

            return $locationArray;
        }
        catch (PDOException $e)
        {
            echo "Impossible to reach the database. Error: " . $e->getMessage();
            return false;
        }               
    }

    public function WriteLocationsToDB($locations, string $table, bool $historical = false)
    {
        $dsn = "mysql:host=" . self::$hostname . ";dbname=" . self::$database;
        $pdo = new PDO($dsn, self::$username, self::$password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $timestamp = time();
        $query = '';

        if( ($historical === true && self::$historical === true ) || ($historical === false && self::$historical === false) )
        {
            try
            {
                foreach($locations as $location)
                {
                    $locationID = $location->__get('id');
                    $locationType = $location->__get('type');
                    $locationName = $location->__get('name');
                    $weather = $location->__get('weather');
                    $clouds = $location->__get('clouds');
                    $waterVapor = $location->__get('waterVapor');
                    $temperature = $location->__get('temperature');
                    $localWater = $location->__get('localWater');
        
                    if($historical === true)
                    {
                        $query .= "INSERT INTO {$table} (location_id, location_type, location_name, weather, clouds, water_vapor, temperature, local_water, timestamp_id) VALUES ('$locationID', '$locationType', '$locationName', '$weather', '$clouds', '$waterVapor', '$temperature', '$localWater', '$timestamp');";
                    }
                    else
                    {
                        $query .= "INSERT INTO {$table} (location_id, location_type, location_name, weather, clouds, water_vapor, temperature, local_water) VALUES ('$locationID', '$locationType', '$locationName', '$weather', '$clouds', '$waterVapor', '$temperature', '$localWater');";
                    }
                }      
                $statement = $pdo->prepare($query);            
                $statement->execute();
                return true;
            }
            catch(PDOException $e)
            {
                echo $e;
                return false;
            }
        }
        else if($historical === true && self::$historical === false)
        {
            echo "\nERROR. The current instance isn't set as Historical.\n";
        }
        else
        {
            echo "\nERROR. The current instance is set as Historical.\n";
        }
        return false;                        
    }

    public function UpdateLocationAtDB(Location $location, string $table)
    {
        $dsn = "mysql:host=" . self::$hostname . ";dbname=" . self::$database;
        $pdo = new PDO($dsn, self::$username, self::$password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try
        {
            $locationID = $location->__get('id');        
            $weather = $location->__get('weather');
            $clouds = $location->__get('clouds');
            $waterVapor = $location->__get('waterVapor');
            $temperature = $location->__get('temperature');
            $localWater = $location->__get('localWater');
    
            $query = "UPDATE {$table} SET weather = {$weather}, clouds = {$clouds}, water_vapor = {$waterVapor}, temperature = {$temperature}, local_water = {$localWater} WHERE location_id = {$locationID};";
            $statement = $pdo->prepare($query);            
            $statement->execute();
            return true;
        }
        catch(PDOException $e)
        {
            echo $e;
            return false;
        }
    }

    public function UpdateAllLocationsAtDB($locations, string $table)
    {
        $dsn = "mysql:host=" . self::$hostname . ";dbname=" . self::$database;
        $pdo = new PDO($dsn, self::$username, self::$password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $query = '';
    
        try
        {
            foreach($locations as $location)
            {
                $locationID = $location->__get('id');
                $weather = $location->__get('weather');
                $clouds = $location->__get('clouds');
                $waterVapor = $location->__get('waterVapor');
                $temperature = $location->__get('temperature');
                $localWater = $location->__get('localWater');
        
                $query .= "UPDATE {$table} SET weather = '{$weather}', clouds = '{$clouds}', water_vapor = '{$waterVapor}', temperature = '{$temperature}', local_water = '{$localWater}' WHERE location_id = '{$locationID}';";
            }      
            $statement = $pdo->prepare($query);            
            $statement->execute();
        }
        catch(PDOException $e)
        {
            echo $e;
            return false;
        }
    }
    #endregion
}

?>