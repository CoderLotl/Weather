<?php

class WeatherMachine
{
    /////////////////////////////////////////////////////////////
    #region - - - CONTROL OF CONSTANT VARIABLES - - -
    // Chances Control
    private const blowingWindChances = 35; // The chances of some 'wind' actually returning some water to the ground without actual rain.
    private const blowingWindReturn = 35; // The amount of water returned to the ground by 'some means'. The system doesn't contemplate the exitence of wind, but water has to return somehow and clouds have to go sometimes.
    private const dewFactor = 35; // The percentage of water vapor that returns back to the ground in the form of dew.
    private const precipitationFactor = 35; // Similar to the above, this controls the percentage of water returned to ground by the rain.
    
    // Dew Control
    // Types of locations: 1: plains/meadows. 2: jungle. 3: woods/forest. 4: desert. 5: mountains. 6: swamp. 7: canyon. 8: lake. 9: taiga. 10: tundra. 11: tundra (deep)
    private const lowDewTypes = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]; // The types of locations which have low atmosphere dew.
    private const highDewTypes = [4, 7]; // The types of locations which have cloud dew.
    
    // Rain and Wind Control
    private const windAndRainCloudReduction = true; // If true, clouds are going to get reduced both by rain and by some kind of wind, returning water to the grund.
    private const firstOrder = 1; // 1 = wind. 2 = rain.
    private const placesWithNoRain = [4, 7]; // The location types id of those places where you don't want it to rain.
    private const placesWithNoWind = [1, 2, 3, 5, 6, 8, 9, 10, 11]; // The location types id of those places where you don't want it to be any wind.
    #endregion
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    #region TICK EXECUTION
    /**
     * Executes a weather tick on a given location passed by param. This function targets a MySQL database.
     * @param mixed $season This is a numeric indicator of the season. The system uses from -42 to 42.
     * @param Location $location The current location-object.
     * @param mixed $dayStage A string indicating the day stage.
     * 
     * @return bool If the tick has been executed successfully, it will return true. Otherwise it will return false.
     */
    public function ExecuteWeatherTick($season, Location $location, $dayStage)
    {
        if($location->__get("type") !== -1) // Locations of type -1 will be ignored completely. This is useful for places where you don't want the calc to happen.
        {
            // CALCULATING AND SETTING THE NEW TEMPERATURE
            $temperature = $this->CalcNewTemperature($season, $location->__get("type"), $dayStage, $location->__get("weather"));
            $location->__set("temperature", $temperature);
        
            // CALCULATING AND APPLYING EVAPORATION
            $this->ApplyWaterEvaporation($location);

            // CALCULATING AND APPLYING CLOUDIFICATION
            $this->ApplyCloudification($location);

            // CALCULATING AND SETTING THE NEW WEATHER
            $this->CalcNewWeather($location);

            // CALCULATING AND SETTING THE DEW
            $this->ApplyDewCalculations($location);

            return true; // Returns the previous instance if the calculation was successful or not.
        }
        else
        {
            return false;
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * Executes a weather tick on a given location passed by param. This function targets a SQLite database.
     * @param mixed $season - This is a numeric indicator of the season. The system uses from -42 to 42.
     * @param Location $location - The current location-object.
     * @param mixed $dayStage - A string indicating the day stage.
     * 
     * @return bool If the tick has been executed successfully, it will return true. Otherwise it will return false.
     */
    public function ExecuteWeatherTickSQLite($season, Location $location, $dayStage)
    {
         // Locations of type 0 or below will be ignored completely. This is useful for places where you don't want the calc to happen.         
        if($location->__get("type") >= 1)
        {            
            // CALCULATING AND SETTING THE NEW TEMPERATURE
            $temperature = $this->CalcNewTemperature($season, $location->__get("type"), $dayStage, $location->__get("weather"));
            $location->__set("temperature", $temperature);
        
            // CALCULATING AND APPLYING EVAPORATION
            $this->ApplyWaterEvaporation($location);

            // CALCULATING AND APPLYING CLOUDIFICATION
            $this->ApplyCloudification($location);

            // CALCULATING AND SETTING THE NEW WEATHER
            $this->CalcNewWeather($location);

            // CALCULATING AND SETTING THE DEW
            $this->ApplyDewCalculations($location);

            //$weatherSystemdataAccess->WriteLocationDataToDB($location, $table); <--- REMOVED FROM HERE. This step should be optional.

            return true; // Returns the previous instance if the calculation was successful or not.
        }
        else
        {
            return false;
        }
    }    
    #endregion
    
    #region DEW METHODS
    /**
     * Calculates the dew chances and sets the dew at the location passed by params if it's due.
     * @param Location $location     
     */
    private function ApplyDewCalculations(Location $location)
    {        
        // WEATHER STAGES: [ 0: Not raining. 1: Dew. 2: Drizzle. 3: Light rain. 4: rain. 5: downpour. 6: storm.]      

        $previousWeather = $location->__get("weather");
        $lowerAtmosphereDew = false;

        if($previousWeather <= 1) // If it's not raining (which would be 2 or more)...
        {
            if(in_array($location->__get("type"), $this::lowDewTypes)) // If the current location has low atmosphere dew...
            {
                $this->ExecuteDewPrecipitation($location, "waterVapor", $lowerAtmosphereDew); // ... I check for low atm. dew.
    
                if($location->__get("weather") === 1 && $previousWeather === 0) // If it was not dewing and it's dewing now at the lower atmosphere...
                {
                    $lowerAtmosphereDew = true; // ... the dew is happening at the lower atmosphere.
                }
            }
            if(in_array($location->__get("type"), $this::highDewTypes)) // If the current location has cloud dew...
            {
                $this->ExecuteDewPrecipitation($location, "clouds", $lowerAtmosphereDew); // ... I check for cloud dew.
            }
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * This function has 2 uses: the param '$source' tells the function if it has to use the 'water vapor', which is present at the lower atmosphere,
     * or if it has to use the 'clouds' as the humidity source. - This is useful to chew on the clouds amount without causing rain directly.
     * @param Location $location
     * @param string $source
     * @param bool $lowerAtmosphereDew
     * 
     * @return [type]
     */
    private function ExecuteDewPrecipitation(Location $location, string $source, bool $lowerAtmosphereDew)
    {
        // - - - SELECTION OF ATMOSPHERIC LEVEL - - -
        if($source === "waterVapor")
        {
            $humiditySource = $location->__get("waterVapor");
            $temperature = $location->__get("temperature");
        }
        else
        {
            $humiditySource = $location->__get("clouds");
            $temperature = $location->__get("temperature");
        }

        // -----------------------------------------------

        // - - - VARIABLES - - -                        
        $saturationPoint = $this->CalcSaturationPoint($temperature);
        $dew = false; // dew flag

        // -----------------------------------------------

        // - - - EXECUTION - - -
        // If humiditySource is over 1, and it's over or at the saturation point...
        if($humiditySource > 1 && $humiditySource >= $saturationPoint)
        {
            // - - - DEW EXECUTION - - -
            $dewAmount = ($humiditySource * $this::dewFactor) / 100;

            if($source === "waterVapor")
            {
                $location->__set("waterVapor", $humiditySource - $dewAmount); // Substraction of dew from the water vapor.
            }
            else
            {
                $location->__set("clouds", $humiditySource - $dewAmount); // Substraction of dew from the clouds.
            }
            
            $location->__set("localWater", $location->__get("localWater") + $dewAmount); // Return of water in the form of dew.
            $dew = true; // It's dewing.
        }
        else
        {
            $dew = false;
        }

        // - - - WEATHER SETTING - - -
        // If it's not going to dew and the weather says it was dewing previously...
        if($dew === false && $location->__get("weather") === 1)
        {
            if($lowerAtmosphereDew === false) // ... and if the dew is not from the lower atmosphere ...
            {
                $location->__set("weather", 0); // ... then the weather is cleared.
            }            
        }
        if($dew === true && $location->__get("weather") === 0)
        {
            $location->__set("weather", 1); // If it's dewing and it wasn't, the weather is set to dew.
        }
    }
    #endregion
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    #region CLOUD METHODS
    /**
     * Calculates the amount of water vapor that's going to turn into clouds. This can only happen if the water vapor is above 1.
     * @param Location $location
     * @param float $locationAdjustment
     * @param int $temperatureAdjustment
     * 
     * @return int
     */
    private function CalcCloudification(Location $location, float $locationAdjustment = 0, int $temperatureAdjustment = 0)
    {        
        if($location->__get("waterVapor") > 1)
        {
            $temperature = $location->__get("temperature");
            $waterVapor = $location->__get("waterVapor");
            $heightAdjustment = 0.4; // This param controls the function's height. The bigger the number, the higher the max result.
            $slopeAdjustment = 10; // This param controls the function's slope around 0. The bigger this param, the softer the slope.
            $locationAdjustment = 0;

            $cloudification = $heightAdjustment * atan(($temperature + $temperatureAdjustment)/$slopeAdjustment) * log($waterVapor) + $locationAdjustment;            
        }
        else
        {
            $cloudification = 0;
        }

        return $cloudification;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * Applies the cloudification procces, which is the process of turning water vapor into clouds.
     * @param Location $location
     * 
     * @return [type]
     */
    private function ApplyCloudification(Location $location)
    {
        $waterVapor = $location->__get("waterVapor");
        $clouds = $location->__get("clouds");

        $cloudification = $this->CalcCloudification($location, 0, 10);             

        if($cloudification !== 0)
        {
            if($waterVapor >= $cloudification)
            {
                $newWaterVapor = $waterVapor - $cloudification;
                $newClouds = $clouds + $cloudification;
                    
                $location->__set("waterVapor", $newWaterVapor);
                $location->__set("clouds", $newClouds);
            }
        }
    }
    #endregion
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    #region EVAPORATION METHODS
    /**
     * Calculates the evaporation of ground level water. Returns the amount of water that should turn into water vapor.
     * Ground water must be greater than 1 for this to happen.
     * @param Location $location
     * @param float $locationAdjustment
     * 
     * @return [type]
     */
    private function CalcWaterEvaporation(Location $location, float $locationAdjustment = 0)
    {
        if($location->__get("localWater") > 1)
        {
            $temperature = $location->__get("temperature");
            $localWater = $location->__get("localWater");
            $heightAdjustment = 0.4; // This param controls the function's height. The bigger the number, the higher the max result.
            $slopeAdjustment = 10; // This param controls the function's slope around 0. The bigger this param, the softer the slope.
            $locationAdjustment = 0;

            $waterEvaporation = $heightAdjustment * atan($temperature/$slopeAdjustment) * log($localWater) + $locationAdjustment;
        }
        else
        {
            $waterEvaporation = 0;
        }
        
        return $waterEvaporation;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    
    /**
     * Calculates the water evaporation and moves the water vapor to the lower atmosphere, reducing the amount of ground level water.
     * @param Location $location
     * 
     * @return [type]
     */
    public function ApplyWaterEvaporation(Location $location)
    {
        $localWater = $location->__get("localWater");
        $waterVapor = $location->__get("waterVapor");

        $waterEvaporation = $this->CalcWaterEvaporation($location);        

        if($waterEvaporation !== 0)
        {
            if($localWater >= $waterEvaporation)
            {
                $newLocalWater = $localWater - $waterEvaporation;
                $newWaterVapor = $waterVapor + $waterEvaporation;
    
                $location->__set("localWater", $newLocalWater);
                $location->__set("waterVapor", $newWaterVapor);                
            }
        }        
    }
    #endregion
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    #region UTILITIES
    /**
     * Calculates the saturation point temperature for a given humidity.
     * @param Location $location
     * 
     * @return [type]
     */
    public function CalcSaturationPointTemp(Location $location)
    {
        $temperature = $location->__get("temperature");
        $relativeHumidity = $this->CalcRelativeHumidity($location);
        $a = 17.27;
        $b = 237.7;

        $member = log($relativeHumidity/100) + $a*$temperature / ($b + $temperature);

        $saturationPointTemp = ($b * $member) / ($a - $member);

        return (int)$saturationPointTemp;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * Calculates the saturation point humidity for the given temperature.
     * @param mixed $temperature
     * 
     * @return [type]
     */
    public function CalcSaturationPoint($temperature)
    {
        $baseEquation = 8.07131 - 1730.63 / (233.426 + $temperature);

        return number_format( pow(10, $baseEquation), 3, '.', '' );
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * Calculates the relative humidity based on the current temperature and humidity.
     * @param Location $location
     * 
     * @return [type]
     */
    public function CalcRelativeHumidity(Location $location)
    {
        $waterVapor = $location->__get("waterVapor");

        $temperature = $location->__get("temperature");

        $saturationPoint = $this->CalcSaturationPoint($temperature);
        
        return number_format( ($waterVapor / $saturationPoint * 100 ), 3, '.', '' );
    }
    #endregion
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    #region WEATHER
    /**
     * 
     * @param Location $location
     * 
     * @return [type]
     */
    private function CalcNewWeather(Location $location)
    {
        echo "\n-----------------------------";
        if($this::firstOrder === 1)
        {
            if($this::windAndRainCloudReduction === true)
            {
                if($this->CheckForBlowingWind($location) === true)
                {
                    $this->BlowSomeWind($location);
                }
            }

            $chancesOfRain = $this->CalcRainChances($location);
            if($chancesOfRain!= false && random_int(0, 100) <= $chancesOfRain)
            {
                $this->CastSomeRain($location);
            }
            else
            {
                $location->__set('weather', 0);
            }
        }
        else
        {
            $chancesOfRain = $this->CalcRainChances($location);
            if($chancesOfRain!= false && random_int(0, 100) <= $chancesOfRain)
            {
                $this->CastSomeRain($location);
                $rainCloudReduction = true;
            }
            else
            {
                $rainCloudReduction = false;
                $location->__set('weather', 0);
            }
            if($this::windAndRainCloudReduction === true && $rainCloudReduction === false) // If the winds are to reduce the clouds and it hasn't rain at this tick...
            {
                if($this->CheckForBlowingWind($location))
                {
                    $this->BlowSomeWind($location);                    
                }
            }
        }
        echo "\n-----------------------------";   
    }
    #endregion
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    #region RAIN
    private function CalcRainChances(Location $location)
    {
        foreach($this::placesWithNoRain as $placeType)
        {
            if($location->__get('type') === $placeType)
            {
                return false; // If the place's type is on the list of places with no rain, the chances of rain are 0.
            }
        }
        $clouds = $location->__get("clouds");
        if($clouds > 2 && $clouds <= 100)
        {
            $chancesOfRain = $clouds-2;
        }
        elseif($clouds <= 2)
        {
            $chancesOfRain = 0;
        }
        else
        {
            $chancesOfRain = 98;
        }

        return $chancesOfRain;
    }

    private function CastSomeRain(Location $location)
    {
        $returningWater = $location->__get("clouds") - ($location->__get("clouds") * $this::precipitationFactor / 100);

        // HERE I HAVE TO SET THE NEW WEATHER. PROBABLY BASED ON THE AMOUNT OF WATER MOVED.
        $location->__set('weather', $this->CalculateRainIntensity($returningWater));

        echo "\nWater moved by the rain: " . $returningWater;
        echo "\nCurrent clouds: " . $location->__get("clouds") . " | Current water: " . $location->__get("localWater");
        $location->__set("localWater", $location->__get("localWater") + $returningWater);
        $location->__set("clouds", $location->__get("clouds") - $returningWater);
        echo "\nNew clouds: " . $location->__get("clouds") . " | New water: " . $location->__get("localWater");
    }

    private function CalculateRainIntensity(float $returningWater)
    {
        if($returningWater <= 20)
        {
            return 2;
        }
        elseif($returningWater <= 40 && $returningWater >= 20)
        {
            return 3;
        }
        elseif($returningWater <= 60 && $returningWater >= 40)
        {
            return 4;
        }
        elseif($returningWater <= 60 && $returningWater >= 80)
        {
            return 5;
        }
        else
        {
            return 6;
        }
    }
    #endregion
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    private function CheckForBlowingWind(Location $location)
    {
        foreach($this::placesWithNoWind as $placeType)
        {
            if($location->__get('type') === $placeType)
            {                
                return false; // If the place's type is on the list of places with no wind, there will be no wind.
            }
        }
        if(random_int(0,100) <= $this::blowingWindChances)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    private function BlowSomeWind(Location $location)
    {
        $returningWater = $location->__get("clouds") - ($location->__get("clouds") * $this::blowingWindReturn / 100);
        echo "\nWater moved by the winds: " . $returningWater;
        echo "\nCurrent clouds: " . $location->__get("clouds") . " | Current water: " . $location->__get("localWater");
        $location->__set("localWater", $location->__get("localWater") + $returningWater);
        $location->__set("clouds", $location->__get("clouds") - $returningWater);
        echo "\nNew clouds: " . $location->__get("clouds") . " | New water: " . $location->__get("localWater");
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * Returns an index to work at based on the name of the day stage. This is only used at the CalcNewTemperature function.
     * @param string $dayStage
     * 
     * @return int
     */
    private function ReturnIndexByDayStage(string $dayStage)
    {
        switch($dayStage)
        {
            case 'midnight':
                return 0;                
            case 'night':
                return 1;                
            case 'dawn':
                return 2;                
            case 'morning':
                return 3;                
            case 'midday':
                return 4;                
            case 'afternoon':
                return 5;                
            case 'evening':
                return 6;                
            case 'dusk':
                return 7;                
        }        
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * Returns a package of parameters based on the location type. Those parameters are meant to be consumed later by other formulas.
     * @param mixed $locationType
     * 
     * @return TemperatureParameters
     */
    private function SetParamsByLocation($locationType)
    {
        // This part calculates the average temperatures by Location.

        $tuning = 0;       // changing this amplitude affects the top and bottom limits.
                            // Increasing this var moves the range to the positive side. Reducing it does the opposite.
        $amplitude = 0;     // changing this amplitude affects the top and bottom limits.
                            // Increasing this var expands the temp range both ways. Reducing it does the opposite.
        $plus = 0;          // This factor adds a plus.
        
        switch($locationType)
        {
            case 1: // Plains / meadows
                $tuning = 12; $amplitude = 2.6; $plus = 0; // -6 to 29 C, 21 to 85 F. - Deviation should go a lil bit up and down. - Night and day changes are small.
                $topLimits =    [-3, -2, -1, 0, 1, 2, 1, 1]; // ['midnight', 'night', 'dawn', 'morning', 'midday', 'afternoon', 'evening', 'dusk']
                $bottomLimits = [-4, -3, -2, -1, 0, 1, 0, 0];
                break;
            case 2: // Jungles 
                $tuning = 23; $amplitude = 0.3; $plus = 0; // 20 to 25 C, 68 to 77 F. - Deviation should only go up. - Night and day changes are small.
                $topLimits =    [-2, -1, 0, 1, 2, 3, 2, 2];
                $bottomLimits = [-3, -2, -1, 0, 1, 2, 1, 1];
                break;
            case 3: // Woods / forests
                $tuning = 11; $amplitude = 2.30; $plus = -3; //  -8 to 23 C, 17 to 73 F. - Deviation should go a lil bit up and down. - Night and day changes are mild.
                $topLimits =    [-3, -2, -1, 0, 1, 2, 1, 1];
                $bottomLimits = [-4, -3, -2, -1, 0, 1, 0, 0];
                break;
            case 4: // Deserts 
                $tuning = 19; $amplitude = 0.5; $plus = 0; // 53 to 77 F, 12 to 25 C. - Deviation should go a lil bit up and down. - Night and day changes are HUGE.
                $topLimits =    [-18, -12, -7, 0, 7, 12, 4, -2];
                $bottomLimits = [-20, -16, -9, 0, 5, 9, 3, -4];
                break;
            case 5: // Mountains
                $tuning = 19; $amplitude = 1; $plus = 0; // Same as deserts... For now.
                $topLimits =    [-10, -7, -4, 0, 7, 10, 4, 4];
                $bottomLimits = [-14, -10, -5, 0, 5, 7, 3, 3];
                break;
            case 6: // Swamps
                $tuning = 23; $amplitude = 1.1; $plus = 0; // 15 to 30 C, 59 to 86 F. - Deviation should go a lil bit up and down. - Night and day changes are small.
                $topLimits =    [-3, -2, -1, 0, 1, 2, 1, 1];
                $bottomLimits = [-4, -3, -2, -1, 0, 1, 0, 0];
                break;
            case 7: // Canyon
                $tuning = 15; $amplitude = 2.7; $plus = 0; // -3 to 32 C, 26.6 to 91.4 F. - Deviation should go a lil bit up and down. - Night and day changes are BIG (down to 10 or even less C).
                $topLimits =    [-7, -6, -5, 0, 4, 5, 4, 4];
                $bottomLimits = [-10, -7, -3, 0, 4, 6, 2, 2];
                break;
            case 8: // Lake
                $tuning = 6.2; $amplitude = 2.4; $plus = 0; // -10 to 22 C, 14 to 71 F. - Deviation should go a somewhat up and down. - Night and day changes are mild.
                $topLimits =    [-5, -4, -5, 0, 4, 5, 4, 4];
                $bottomLimits = [-8, -5, -3, 0, 4, 6, 2, 2];
                break;
            case 9: // Taiga
                $tuning = 1; $amplitude = 1; $plus = 0; // -6 to 7 C, 21 to 44 F. - Deviation should be minimal. - Night and day changes are big, but only in the night's way.
                $topLimits =    [-3, -2, -1, 0, 1, 2, 1, 1];
                $bottomLimits = [-4, -3, -2, -1, 0, 1, 0, 0];
                break;
            case 10: // Tundra
                $tuning = 1; $amplitude = 2.4; $plus = -2; // -17 to 15 C, 1.4 to 59 F. - Deviation should go up and down. Night and day changes don't exist (it's either always day or night).
                $topLimits =    [1, 1, 1, 1, 1, 1, 1, 1];
                $bottomLimits = [-6, -4, -3, -1, 0, 1, 0, 0];
                break;
            case 11: // Tundra (deep)
                $tuning = 1; $amplitude = 2.4; $plus = -12; // -27 to 5 C, -16 to 41 F. - Deviation should be minimal. - Night and day changes don't exist (it's either always day or night).
                $topLimits =    [1, 1, 1, 1, 1, 1, 1, 1];
                $bottomLimits = [-6, -4, -3, -1, 0, 1, 0, 0];
                break;
        }

        return new TemperatureParameters($tuning, $amplitude, $plus, $topLimits, $bottomLimits);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * Calculates the new temperature for the location based on the season factor, the location type, the day stage, and the weather.
     * @param int $season
     * @param int $locationType
     * @param string $dayStage
     * @param mixed $weather
     * 
     * @return [type]
     */
    private function CalcNewTemperature(int $season, int $locationType, string $dayStage, $weather)
    {
        // NOTE: Temperatures are in C here. For a F value there needs to be a conversion step.

        $timeDivider = 6; // Since the system has been designed to work with units of 7 days and the seasons have 42 days (6 weeks), this is
                          // an important factor. If the seasons's length ever changes, you can tune it here.
        
        // ------------------------------------------------------ [ INDEX TO USE BY STAGE OF THE DAY ]

        $index = $this->ReturnIndexByDayStage($dayStage);

        // ------------------------------------------------------ [ PARAMS TO USE BY LOCATION ]
                
        $params = $this->SetParamsByLocation($locationType); // In order to see the values of the parameters, check the function.

        $tuning = $params->GetTunning(); $amplitude = $params->GetAmplitude(); $plus = $params->GetAmplitude();
        $topLimits = $params->GetTopLimits($index); $bottomLimits = $params->GetBottomLimits($index);
        
        // ------------------------------------------------------ [ WEATHER EFFECT'S PARAM ]

        $weatherEffect = $this->SetTempModificationParameterByWeather($weather);

        // ------------------------------------------------------ [ CALCULATIONS ]
        
        $averageTemperature = (int)($tuning + ($amplitude * $season / $timeDivider) + $plus);

        $temperature = rand( ( $averageTemperature + $bottomLimits ), ( $averageTemperature + $topLimits ) );

        $temperature = ( $temperature + ( ( $temperature * $weatherEffect ) / 100 ) );

        return $temperature;
        // Most data gathered from https://earthobservatory.nasa.gov/biome/
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    private function SetTempModificationParameterByWeather($weather)
    {
        switch($weather)
        {
            // WEATHER STAGES: [0: Not raining. 1: Dew. 2: Light rain. 3: rain. 4: downpour. 5: storm.]
            case 0:
                $param = 0;
                break;
            case 1:
                $param = 0;
                break;
            case 2:
                $param = 0;
                break;
            case 3:
                $param = 0;
                break;
            case 4:
                $param = 0;
                break;
            case 5:
                $param = 0;
                break;
        }

        return $param;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
}

?>
