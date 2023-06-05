<?php

/**
 * Provides connection with a given SQLite DB where the tables with the season and locations are stored.
 * Provides public functions to read, update and write both season's and location's data.
 */
class WeatherSystemSQLiteDataAccess
{
    private static $DBPath;
    private static $historical;    

    public static function SetDBPath(string $dbPath, bool $historical = false)
    {
        self::$DBPath = $dbPath;
    }

    public static function GetDBParams($name)
    {
        switch($name)
        {
            case 'path':
                return self::$DBPath;
            case 'historical':
                return self::$historical;
        }
    }

    /**
     * Creates the required tables at the previously specified database. This doesn't fill the tables with any data.
     * @return bool True if success, False if fail.
     */
    public function CreateTables()
    {
        $pdo = new PDO('sqlite:' . self::$DBPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if(self::$DBPath !== null)
        {
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
        else
        {
            echo "\nThe database path is not set.\n";
            return false;
        }
    }

    public function ReadSeasonDataFromDB(string $table)
    {
        $pdo = new PDO('sqlite:' . self::$DBPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $seasonControl = new SeasonControl();

        try
        {
            $statement = $pdo->prepare("SELECT season_day, season_direction FROM {$table}");
            $statement->execute();        
            $data = $statement->fetch(PDO::FETCH_ASSOC);
    
            if(count($data) > 0)
            {
                $seasonControl->SetDay($data['season_day']);
        
                if($data['season_direction'] === 0)
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
            return false;
        }
    }

    public function UpdateSeasonDataToDB(SeasonControl $seasonControl, string $table)
    {
        $pdo = new PDO('sqlite:' . self::$DBPath);
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
    
            $statement =  $pdo->prepare($query);
            $statement->execute();
            return true;
        }
        catch(PDOException $e)
        {
            echo $e;
            return false;
        }
    }

    public function ReadLocationDataFromDB(string $table,  $limit = null)
    {        
        $locationArray = [];
        $pdo = new PDO('sqlite:' . self::$DBPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try
        {
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

    public function UpdateLocationDataToDB(Location $location, string $table)
    {
        $pdo = new PDO('sqlite:' . self::$DBPath);
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

    public function UpdateAllLocationsDataToDB($locations, string $table)
    {
        $pdo = new PDO('sqlite:' . self::$DBPath);
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
}

?>