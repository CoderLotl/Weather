<?php

class WeatherMachine
{
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    #region TICK EXECUTION
    /**
     * Executes a weather tick on a given location passed by param.
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
            if(applyTemperature === true)
            {
                $temperature = $this->CalcNewTemperature($season, $location->__get("type"), $dayStage, $location->__get("weather"));
                $location->__set("temperature", $temperature);

                if(applyWeather === true)
                {
                    // CALCULATING AND APPLYING EVAPORATION
                    $this->ApplyWaterEvaporation($location);
        
                    // CALCULATING AND APPLYING CLOUDIFICATION
                    $this->ApplyCloudification($location);
        
                    // CALCULATING AND SETTING THE NEW WEATHER
                    $this->CalcNewWeather($location);
        
                    // CALCULATING AND SETTING THE DEW
                    $this->ApplyDewCalculations($location);
        
                    $this->CheckWaterLimits($location);
                }
            }
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
            if(in_array($location->__get("type"), lowDewTypes)) // If the current location has low atmosphere dew...
            {
                $this->ExecuteDewPrecipitation($location, "waterVapor", $lowerAtmosphereDew); // ... I check for low atm. dew.
    
                if($location->__get("weather") === 1 && $previousWeather === 0) // If it was not dewing and it's dewing now at the lower atmosphere...
                {
                    $lowerAtmosphereDew = true; // ... the dew is happening at the lower atmosphere.
                }
            }
            if(in_array($location->__get("type"), highDewTypes)) // If the current location has cloud dew...
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
            $dewAmount = ($humiditySource * dewFactor) / 100;

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
     * Checks the total liquids present at the location and corrects any deviation based on the set values for the location type.
     * The corrections are performed at whichever of the 3 areas of water at the location there's the most water.
     * @param Location $location
     * 
     * @return [type]
     */
    private function CheckWaterLimits(Location $location)
    {
        $totalLiquids = ($location->__get('clouds') + $location->__get('localWater') + $location->__get('waterVapor'));
        $clouds = $location->__get('clouds');
        $localWater = $location->__get('localWater');
        $waterVapor = $location->__get('waterVapor');

        foreach(waterLimits as $limit => $value)
        {
            if($location->__get('type') === $limit)
            {                
                if($totalLiquids > ($value + 0.05) )
                {
                    while ($totalLiquids > ($value + 0.05) )
                    {
                        if($clouds > $localWater && $clouds > $waterVapor)
                        {
                            $location->__set('clouds', ($clouds - 0.5));
                        }
                        elseif($localWater > $clouds && $localWater > $waterVapor)
                        {
                            $location->__set('localWater', ($localWater - 0.5));
                        }
                        else
                        {
                            $location->__set('waterVapor', ($waterVapor - 0.5));
                        }
                    }
                    echo "\nLocation's liquids have been corrected.\n";
                }
                elseif($totalLiquids < ($value - 0.05) )
                {
                    while($totalLiquids < ($value - 0.05) )
                    {
                        if($clouds > $localWater && $clouds > $waterVapor)
                        {
                            $location->__set('clouds', ($clouds + 0.5));
                        }
                        elseif($localWater > $clouds && $localWater > $waterVapor)
                        {
                            $location->__set('localWater', ($localWater + 0.5));
                        }
                        else
                        {
                            $location->__set('waterVapor', ($waterVapor + 0.5));
                        }
                    }
                    echo "\nLocation's liquids have been corrected.\n";
                }                
                break;
            }
        }        
    }

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
     * Calculates the saturation point of humidity for the given temperature.
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
     * Calculates the relative humidity based on the current temperature and water vapor.
     * Returns a string with the number cut to 3 decimals.
     * @param Location $location
     * 
     * @return string
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
     * Calculates the weather for the current tick at the given location based on its own parameters
     * and the static constants of the Weather Machine (this class).
     * @param Location $location
     * 
     * @return void
     */
    private function CalcNewWeather(Location $location)
    {
        echo "\n-----------------------------";
        if(firstOrder === 1)
        {
            if(windAndRainCloudReduction === true)
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
            if(windAndRainCloudReduction === true && $rainCloudReduction === false) // If the winds are to reduce the clouds and it hasn't rain at this tick...
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
    /**
     * Calculates and returns the chances of rain for the location.
     * @param Location $location
     * 
     * @return int|false chances of rain or false if it doesn't rain at the location.
     */
    private function CalcRainChances(Location $location)
    {
        foreach(placesWithNoRain as $placeType)
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

    /**
     * Turns clouds into rain, returning water to the ground level.
     * The percentage of water returned is set at precipitationFactor.     
     * @param Location $location
     * 
     * @return void
     */
    private function CastSomeRain(Location $location)
    {
        $returningWater = $location->__get("clouds") - ($location->__get("clouds") * precipitationFactor / 100);
        
        $location->__set('weather', $this->CalculateRainIntensity($returningWater));

        echo "\nWater moved by the rain: " . $returningWater;
        echo "\nCurrent clouds: " . $location->__get("clouds") . " | Current water: " . $location->__get("localWater");
        $location->__set("localWater", $location->__get("localWater") + $returningWater);
        $location->__set("clouds", $location->__get("clouds") - $returningWater);
        echo "\nNew clouds: " . $location->__get("clouds") . " | New water: " . $location->__get("localWater");
    }

    /**
     * Sets the rain intensity based on the amount of water returning to the ground.
     * @param float $returningWater
     * 
     * @return int
     */
    private function CalculateRainIntensity(float $returningWater)
    {
        if($returningWater <= 10)
        {
            return 2;
        }
        elseif($returningWater <= 15 && $returningWater > 10)
        {
            return 3;
        }
        elseif($returningWater <= 20 && $returningWater > 15)
        {
            return 4;
        }
        elseif($returningWater <= 25 && $returningWater > 20)
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
    #region WIND
    /**
     * This function checks if the location has wind or not and, if possitive, calculates the chance of some gust of wind happening.
     * Wind is a 'magical' way of returning water to the ground silently. That is: without actually making it rain.
     * @param Location $location The location we are working with.
     * 
     * @return true|false True if there's chance of wind, false if there's not or if the place has no wind.
     */
    private function CheckForBlowingWind(Location $location)
    {
        foreach(placesWithNoWind as $placeType)
        {
            if($location->__get('type') === $placeType)
            {                
                return false; // If the place's type is on the list of places with no wind, there will be no wind.
            }
        }
        if(random_int(0,100) <= blowingWindChances)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Removes some water from the clouds and returns it to the ground without directly affecting the weather. Useful for those places that have no rain and no dew but you need to complete the water cycle anyway.
     * @param Location $location The location we are working with.
     * 
     * @return void.
     */
    private function BlowSomeWind(Location $location)
    {
        $returningWater = $location->__get("clouds") - ($location->__get("clouds") * blowingWindReturn / 100);
        echo "\nWater moved by the winds: " . $returningWater;
        echo "\nCurrent clouds: " . $location->__get("clouds") . " | Current water: " . $location->__get("localWater");
        $location->__set("localWater", $location->__get("localWater") + $returningWater);
        $location->__set("clouds", $location->__get("clouds") - $returningWater);
        echo "\nNew clouds: " . $location->__get("clouds") . " | New water: " . $location->__get("localWater");
    }
    #endregion
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    #region TEMPERATURE
    /**
     * Returns an index to work at based on the name of the day stage. This is only used at the CalcNewTemperature function.
     * @param string $dayStage
     * 
     * @return int
     */
    private function ReturnIndexByDayStage(string $dayStage)
    {
        return daystage[$dayStage];
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * Returns a package of parameters based on the location type. Those parameters are meant to be consumed later by other formulas.
     * @param int $locationType The numeric value that represents the location type.
     * 
     * @return mixed A package class containing several numeric arguments to be consumed by CalcNewTemperature.
     */
    private function SetParamsByLocation(int $locationType)
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

        $temperatureParams =
        [
            'tuning' => $tuning,
            'amplitude' => $amplitude,
            'plus' => $plus,
            'topLimits' => $topLimits,
            'bottomLimits' => $bottomLimits
        ];

        return $temperatureParams;
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

        $timeDivider = time_divider; // Since the system has been designed to work with units of 7 days and the seasons have 42 days (6 weeks), this is
                          // an important factor. If the seasons's length ever changes, you can tune it here.
        
        // ------------------------------------------------------ [ INDEX TO USE BY STAGE OF THE DAY ]

        $index = $this->ReturnIndexByDayStage($dayStage);

        // ------------------------------------------------------ [ PARAMS TO USE BY LOCATION ]
                
        $params = $this->SetParamsByLocation($locationType); // In order to see the values of the parameters, check the function.

        $tuning = $params['tuning']; $amplitude = $params['amplitude']; $plus = $params['plus'];
        $topLimits = $params['topLimits'][$index]; $bottomLimits = $params['bottomLimits'][$index];
        
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
                return 0;                
            case 1:
                return 0;                
            case 2:
                return 0;                
            case 3:
                return 0;                
            case 4:
                return 0;
            case 5:
                return 0;
         }        
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    // - - - - - - - - - - - - - - - [ EXECUTION ]
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    public function RunSingleDayStage(int $option = 1)
    {
        $locationArray = '';
        $seasonControl = '';
        $dataAccess = '';

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

        if($dataAccess::GetDBParams('histoical') === true)
        {
            $locationArray = $dataAccess->ReadLocationDataFromDB('locs', 2);
        }
        else
        {
            $locationArray = $dataAccess->ReadLocationDataFromDB("locs");
        }

        $initialDayStage = $seasonControl->GetDayStage();

        $dayStage = array_search($initialDayStage, daystage); // refactor this
        echo "\n*** LOCATION BLOCK ***";
        echo "\nDay Stage: {$dayStage}";
    
        echo "\nLocations:\n\n";        
        
        foreach($locationArray as $newLocation)
        {
            if($this->ExecuteWeatherTick($seasonControl->GetDay(), $newLocation, $dayStage) == true)
            {                
                echo $newLocation . "\n-------\n";

                $totalLiquids = ($newLocation->__get('clouds') + $newLocation->__get('localWater') + $newLocation->__get('waterVapor'));
                echo "\nTOTAL LIQUIDS: {$totalLiquids}\n\n";
            }            
        }
        $initialDayStage++;

        if($initialDayStage > 7)
        {
            $seasonControl->SetDayStage(0);
            $seasonControl->Tick();
        }
        else
        {
            $seasonControl->SetDayStage($initialDayStage);            
        }

        $dataAccess->UpdateSeasonDataToDB($seasonControl, 'worlds');
        $dataAccess->UpdateAllLocationsAtDB($locationArray, 'locs');
        if($dataAccess::GetDBParams('historical') === true)
        {
            $dataAccess->WriteLocationsToDB($locationArray, 'test');
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    public function RunTillEndOfDay(int $option = 1)
    {
        $locationArray = '';
        $seasonControl = '';
        $dataAccess = '';

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

        if($dataAccess::GetDBParams('histoical') === true)
        {
            $locationArray = $dataAccess->ReadLocationDataFromDB('locs', 2);
        }
        else
        {
            $locationArray = $dataAccess->ReadLocationDataFromDB("locs");
        }

        $initialDayStage = $seasonControl->GetDayStage();

        for($i = $initialDayStage; $i < 8; $i++)
        {
            $dayStage = array_search($initialDayStage, daystage); // refactor this
            echo "\n*** LOCATION BLOCK ***";
            echo "\nDay Stage: {$dayStage}";
        
            echo "\nLocations:\n\n";        
            
            foreach($locationArray as $newLocation)
            {
                if($this->ExecuteWeatherTick($seasonControl->GetDay(), $newLocation, $dayStage) == true)
                {                
                    echo $newLocation . "\n-------\n";

                    $totalLiquids = ($newLocation->__get('clouds') + $newLocation->__get('localWater') + $newLocation->__get('waterVapor'));
                    echo "\nTOTAL LIQUIDS: {$totalLiquids}\n\n";
                }            
            }
            $initialDayStage++;
        }

        $seasonControl->SetDayStage(0);
        $seasonControl->Tick();

        $dataAccess->UpdateSeasonDataToDB($seasonControl, 'worlds');
        $dataAccess->UpdateAllLocationsAtDB($locationArray, 'locs');
        if($dataAccess::GetDBParams('historical') === true)
        {
            $dataAccess->WriteLocationsToDB($locationArray, 'test');
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * @param int $option 1 = SQLITE - - - 2 = MYSQL
     * @param int $daysToRun Amount of days to run
     * 
     * @return [type]
     */
    public function RunDays(int $option = 1, int $daysToRun = 1)
    {        
        $locationArray = '';
        $seasonControl = '';
        $dataAccess = '';        

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

        if($dataAccess::GetDBParams('histoical') === true)
        {
            $locationArray = $dataAccess->ReadLocationDataFromDB('locs', 2);
        }
        else
        {
            $locationArray = $dataAccess->ReadLocationDataFromDB("locs");
        }

        $initialDayStage = $seasonControl->GetDayStage();

        for($i = 0; $i < $daysToRun; $i ++)
        {
            for($j = 0; $j < 8; $j ++)
            {
                $dayStage = array_search($initialDayStage, daystage); // refactor this
                echo "\n*** LOCATION BLOCK ***";
                echo "\nDay Stage: {$dayStage}";
            
                echo "\nLocations:\n\n";        
                
                foreach($locationArray as $newLocation)
                {
                    if($this->ExecuteWeatherTick($seasonControl->GetDay(), $newLocation, $dayStage) == true)
                    {                
                        echo $newLocation . "\n-------\n";

                        $totalLiquids = ($newLocation->__get('clouds') + $newLocation->__get('localWater') + $newLocation->__get('waterVapor'));
                        echo "\nTOTAL LIQUIDS: {$totalLiquids}\n\n";
                    }            
                }

                if($initialDayStage + 1 <= 7)
                {
                    $initialDayStage++;
                }
                else
                {
                    $initialDayStage = 0;
                    $seasonControl->Tick();
                }
            }
        }

        $dataAccess->UpdateSeasonDataToDB($seasonControl, 'worlds');
        $dataAccess->UpdateAllLocationsAtDB($locationArray, 'locs');
        if($dataAccess::GetDBParams('historical') === true)
        {
            $dataAccess->WriteLocationsToDB($locationArray, 'test');
        }
    }
    #endregion
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
}

?>