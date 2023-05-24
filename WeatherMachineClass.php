<?php

class WeatherMachine
{
    // - - - CONTROL OF CONSTANT VARIABLES - - -

    // Chances Control
    private const blowingWindChances = 35;
    private const blowingWindReturn = 35; // The amount of water returned to the ground by 'some means'. The system doesn't contemplate the exitence of wind, but water has to return somehow and clouds have to go sometimes.
    private const dewFactor = 35; // The percentage of water vapor that returns back to the ground in the form of dew.
    private const precipitationFactor = 35; // Similar to the above, this controls the percentage of water returned to ground by the rain.
    
    // Dew Control
    // Types of locations: 1: plains/meadows. 2: jungle. 3: woods/forest. 4: desert. 5: mountains. 6: swamp. 7: canyon. 8: lake. 9: taiga. 10: tundra. 11: tundra (deep)
    private const lowDewTypes = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11); // The types of locations which have low atmosphere dew.
    private const highDewTypes = array(4); // The types of locations which have cloud dew.
    
    // Rain and Wind Control
    private const windAndRainCloudReduction = true;
    private const firstOrder = 1; // 1 = wind. 2 = rain.


    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * @param mixed $season This is a numeric indicator of the season. The system uses from -42 to 42.
     * @param Location $location The current location-object.
     * @param mixed $dayStage A string indicating the day stage.
     * @param WeatherSystemDataAccess $WeatherSystemdataAccess The control which communicates with the data base. This one uses MySQL.
     * @param string $table The table's name it has to work with. This is where the locations are stored.
     * 
     * @return bool If the tick has been executed successfully, it will return true. Otherwise it will return false.
     */
    public function ExecuteWeatherTick($season, Location $location, $dayStage, WeatherSystemDataAccess $WeatherSystemdataAccess, string $table)
    {
        if($location->__get("type") != -1) // Locations of type -1 will be ignored completely. This is useful for places where you don't want the calc to happen.
        {
            // CALCULATING AND SETTING THE NEW TEMPERATURE
            $temperature = $this->CalcNewTemperature($season, $location->__get("type"), $dayStage, $location->__get("weather"));
            $location->__set("temperature", $temperature);
        
            // CALCULATING AND SETTING THE NEW WEATHER
            $weather = $this->CalcNewWeather($location);

            $WeatherSystemdataAccess->WriteLocationDataToDB($location, $table);

            return true; // Returns the previous instance if the calculation was successful or not.
        }
        else
        {
            return false;
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /**
     * @param mixed $season - This is a numeric indicator of the season. The system uses from -42 to 42.
     * @param Location $location - The current location-object.
     * @param mixed $dayStage - A string indicating the day stage.
     * @param WeatherSystemDataAccess $WeatherSystemdataAccess - The control which communicates with the data base. This one use SQLite.
     * @param string $table - The table's name it has to work with. This is where the locations are stored.
     * 
     * @return [type] If the tick has been executed successfully, it will return true. Otherwise it will return false.
     */
    public function ExecuteWeatherTickSQLite($season, Location $location, $dayStage, WeatherSystemSQLiteDataAccess $weatherSystemdataAccess, string $table)
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
            //$this->CalcNewWeather($location);

            //$this->ApplyDewCalculations($location);

            $weatherSystemdataAccess->WriteLocationDataToDB($location, $table);

            return true; // Returns the previous instance if the calculation was successful or not.
        }
        else
        {
            return false;
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    /* This function works with 2 arrays:
        $lowDewTypes: this array has the numeric types of those locations where dew is gonna use the low atmosphere water vapor for the dew.
        $highDewTypes: this array has the numeric types of those locations where dew is gonna use the clouds for the dew.

        Both things can happen. This is useful to chew humidity from the rains in a subtle way, preventing some places from getting rain.
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
    
                if($location->__get("weather") == 1 && $previousWeather == 0) // If it was not dewing and it's dewing now at the lower atmosphere...
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

    // This function has 2 uses: the param '$source' tells the function if it has to use the 'water vapor', which is present at the lower atmosphere,
    // or if it has to use the 'clouds' as the humidity source. - This is useful to chew on the clouds amount without causing rain directly.
    private function ExecuteDewPrecipitation(Location $location, string $source, bool $lowerAtmosphereDew)
    {
        // - - - SELECTION OF ATMOSPHERIC LEVEL - - -
        if($source == "waterVapor")
        {
            $humiditySource = $location->__get("waterVapor");
            $temperature = $location->__get("temperature");
        }
        else
        {
            $humiditySource = $location->__get("clouds");
            $temperature = $location->__get("temperature");
        }

        // - - - VARIABLES - - -                        
        $saturationPoint = $this->CalcSaturationPoint($temperature);
        $dew = false;        

        // - - - EXECUTION - - -
        // If humiditySource is over 1, and it's over or at the saturation point...
        if($humiditySource > 1 && $humiditySource >= $saturationPoint)
        {
            // - - - DEW EXECUTION - - -
            $dewAmount = ($humiditySource * $this::dewFactor) / 100;

            if($source = "waterVapor")
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
        if($dew == false && $location->__get("weather") == 1)
        {
            if($lowerAtmosphereDew == false) // ... and if the dew is not from the lower atmosphere ...
            {
                $location->__set("weather", 0); // ... then the weather is cleared.
            }            
        }
        if($dew == true && $location->__get("weather") == 0)
        {
            $location->__set("weather", 1); // If it's dewing and it wasn't, the weather is set to dew.
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    private function CalcCloudification(Location $location, float $locationAdjustment = 0, int $temperatureAdjustment = 0)
    {        
        if($location->__get("waterVapor") > 1)
        {
            $temperature = $location->__get("temperature");
            $waterVapor = $location->__get("waterVapor");
            $heightAdjustment = 0.4; // This param controls the function's height. The bigger the number, the higher the max result.
            $slopeAdjustment = 10; // This param controls the function's slope around 0. The bigger this param, the softer the slope.
            $locationAdjustment = 0;

            $cloudification = $heightAdjustment * atan(($temperature - $temperatureAdjustment)/$slopeAdjustment) * log($waterVapor) + $locationAdjustment;            
        }
        else
        {
            $cloudification = 0;
        }

        return $cloudification;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    private function ApplyCloudification(Location $location)
    {
        $waterVapor = $location->__get("waterVapor");
        $clouds = $location->__get("clouds");

        $cloudification = $this->CalcCloudification($location, 0, 10);
        $cloudification = $this->CalcCloudification($location, 0, 10);

        if($cloudification != 0)
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

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

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
    
    public function ApplyWaterEvaporation(Location $location)
    {
        $localWater = $location->__get("localWater");
        $waterVapor = $location->__get("waterVapor");

        $waterEvaporation = $this->CalcWaterEvaporation($location);        

        if($waterEvaporation != 0)
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

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

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

    public function CalcSaturationPoint($temperature)
    {
        $baseEquation = 8.07131 - 1730.63 / (233.426 + $temperature);

        return number_format( pow(10, $baseEquation), 3, '.', '' );
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    public function CalcRelativeHumidity(Location $location)
    {
        $waterVapor = $location->__get("waterVapor");

        $temperature = $location->__get("temperature");

        $saturationPoint = $this->CalcSaturationPoint($temperature);
        
        return number_format( ($waterVapor / $saturationPoint * 100 ), 3, '.', '' );
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    private function CalcNewWeather(Location $location)
    {
        echo "\n-----------------------------";
        if($this::firstOrder == 1)
        {
            $windCloudReduction = $this->CheckForBlowingWind($location);

            if($this::windAndRainCloudReduction == true || ($this::windAndRainCloudReduction == false && $windCloudReduction == false))
            {
                $chancesOfRain = $this->CalcRainChances($location);
                if(random_int(0, 100) <= $chancesOfRain)
                {
                    $returningWater = $location->__get("clouds") - ($location->__get("clouds") * $this::precipitationFactor / 100);
                    echo "\nWater moved by the rain: " . $returningWater;
                    echo "\nCurrent clouds: " . $location->__get("clouds") . " | Current water: " . $location->__get("localWater");
                    $location->__set("localWater", $location->__get("localWater") + $returningWater);
                    echo "\nNew clouds: " . $location->__get("clouds") . " | New water: " . $location->__get("localWater");
                    $location->__set("clouds", $location->__get("clouds") - $returningWater);
                }
            }
        }
        else
        {
            $chancesOfRain = $this->CalcRainChances($location);
            if(random_int(0, 100) <= $chancesOfRain)
            {
                $returningWater = $location->__get("clouds") - ($location->__get("clouds") * $this::precipitationFactor / 100);
                echo "\nWater moved by the rain: " . $returningWater;
                echo "\nCurrent clouds: " . $location->__get("clouds") . " | Current water: " . $location->__get("localWater");
                $location->__set("localWater", $location->__get("localWater") + $returningWater);
                $location->__set("clouds", $location->__get("clouds") - $returningWater);
                echo "\nNew clouds: " . $location->__get("clouds") . " | New water: " . $location->__get("localWater");
                $rainCloudReduction = true;
            }
            else
            {
                $rainCloudReduction = false;
            }
            if($this::windAndRainCloudReduction == true || ($this::windAndRainCloudReduction == false && $rainCloudReduction == false))
            {
                $this->CheckForBlowingWind($location);                
            }
        }
        echo "\n-----------------------------";   
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    private function CalcRainChances(Location $location)
    {
        $clouds = $location->__get("clouds");
        if($clouds > 2)
        {
            if($clouds <= 100)
            {
                $chancesOfRain = $clouds-2;
            }
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

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    private function CheckForBlowingWind(Location $location)
    {
        if(random_int(0,100) <= $this::blowingWindChances)
        {
            $returningWater = $location->__get("clouds") - ($location->__get("clouds") * $this::blowingWindReturn / 100);
            echo "\nWater moved by the winds: " . $returningWater;
            echo "\nCurrent clouds: " . $location->__get("clouds") . " | Current water: " . $location->__get("localWater");
            $location->__set("localWater", $location->__get("localWater") + $returningWater);
            $location->__set("clouds", $location->__get("clouds") - $returningWater);
            echo "\nNew clouds: " . $location->__get("clouds") . " | New water: " . $location->__get("localWater");
            return true;
        }
        else
        {
            return false;
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

    private function ReturnIndexByDayStage(string $dayStage)
    {
        switch($dayStage)
        {
            case 'midnight':
                $indexToReturn = 0;
                break;
            case 'night':
                $indexToReturn = 1;
                break;
            case 'dawn':
                $indexToReturn = 2;
                break;
            case 'morning':
                $indexToReturn = 3;
                break;
            case 'midday':
                $indexToReturn = 4;
                break;
            case 'afternoon':
                $indexToReturn = 5;
                break;
            case 'evening':
                $indexToReturn = 6;
                break;
            case 'dusk':
                $indexToReturn = 7;
                break;
        }

        return $indexToReturn;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

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
