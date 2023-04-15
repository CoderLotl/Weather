<?php

class WeatherMachine
{
    public function ExecuteWeatherTick($season, Location $location, $dayStage, WeatherSystemDataAccess $WeatherSystemdataAccess, string $table)
    {
        if($location->GetLocationType() != -1) // Locations of type -1 will be ignored completely. This is useful for places where you don't want the calc to happen.
        {
            // CALCULATING AND SETTING THE NEW TEMPERATURE
            $temperature = $this->CalcNewTemperature($season, $location->GetLocationType(), $dayStage, $location->GetWeather());
            $location->SetTemperature($temperature);
        
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

    public function ExecuteWeatherTickSQLite($season, Location $location, $dayStage, WeatherSystemSQLiteDataAccess $weatherSystemdataAccess, string $table)
    {
        if($location->GetLocationType() != -1) // Locations of type -1 will be ignored completely. This is useful for places where you don't want the calc to happen.
        {            
            // CALCULATING AND SETTING THE NEW TEMPERATURE
            $temperature = $this->CalcNewTemperature($season, $location->GetLocationType(), $dayStage, $location->GetWeather());
            $location->SetTemperature($temperature);
        
            // CALCULATING AND APPLYING EVAPORATION
            $this->ApplyWaterEvaporation($location);

            // CALCULATING AND APPLYING CLOUDIFICATION
            $this->ApplyCloudification($location);

            // CALCULATING AND SETTING THE NEW WEATHER
            $weather = $this->CalcNewWeather($location);

            $this->ExecuteDewPrecipitation($location);

            $weatherSystemdataAccess->WriteLocationDataToDB($location, $table);

            return true; // Returns the previous instance if the calculation was successful or not.
        }
        else
        {
            return false;
        }
    }

    private function ExecuteDewPrecipitation(Location $location)
    {
        $waterVapor = $location->GetWaterVapor();
        $saturationPoint = $this->CalcSaturationPoint($location->GetTemperature());
        $dew = false;

        if($waterVapor >1 && $waterVapor >= $saturationPoint)
        {
            $dewAmount = ($waterVapor * 35) / 100;
            $location->SetWaterVapor($waterVapor - $dewAmount);
            $location->SetLocalWater($location->GetLocalWater() + $dewAmount);
            $dew = true;
        }
        else
        {
            $dew = false;
        }

        if($dew == false && $location->GetWeather() == 1)
        {
            $location->SetWeather(0);
        }
        if($dew == true && $location->GetWeather() == 0)
        {
            $location->SetWeather(1);
        }
    }

    private function CalcCloudification(Location $location, float $locationAdjustment = 0, int $temperatureAdjustment = 0)
    {        
        if($location->GetWaterVapor() > 1)
        {
            $temperature = $location->GetTemperature();
            $waterVapor = $location->GetWaterVapor();
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

    private function ApplyCloudification(Location $location)
    {
        $waterVapor = $location->GetWaterVapor();
        $clouds = $location->GetClouds();

        $cloudification = $this->CalcCloudification($location, 0, 10);

        if($cloudification != 0)
        {
            if($waterVapor >= $cloudification)
            {
                $newWaterVapor = $waterVapor - $cloudification;
                $newClouds = $clouds + $cloudification;
                    
                $location->SetWaterVapor($newWaterVapor);
                $location->SetClouds($newClouds);
            }
        }
    }

    private function CalcWaterEvaporation(Location $location, float $locationAdjustment = 0)
    {
        if($location->GetLocalWater() > 1)
        {
            $temperature = $location->GetTemperature();
            $localWater = $location->GetLocalWater();
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
    
    public function ApplyWaterEvaporation(Location $location)
    {
        $localWater = $location->GetLocalWater();
        $waterVapor = $location->GetWaterVapor();

        $waterEvaporation = $this->CalcWaterEvaporation($location);        

        if($waterEvaporation != 0)
        {
            if($localWater >= $waterEvaporation)
            {
                $newLocalWater = $localWater - $waterEvaporation;
                $newWaterVapor = $waterVapor + $waterEvaporation;
    
                $location->SetLocalWater($newLocalWater);
                $location->SetWaterVapor($newWaterVapor);                
            }
        }        
    }

    public function CalcSaturationPointTemp(Location $location)
    {
        $temperature = $location->GetTemperature();
        $relativeHumidity = $this->CalcRelativeHumidity($location);
        $a = 17.27;
        $b = 237.7;

        $member = log($relativeHumidity/100) + $a*$temperature / ($b + $temperature);

        $saturationPointTemp = ($b * $member) / ($a - $member);

        return (int)$saturationPointTemp;
    }

    public function CalcSaturationPoint($temperature)
    {
        $baseEquation = 8.07131 - 1730.63 / (233.426 + $temperature);

        return number_format( pow(10, $baseEquation), 3, '.', '' );
    }

    public function CalcRelativeHumidity(Location $location)
    {
        $waterVapor = $location->GetWaterVapor();

        $temperature = $location->GetTemperature();

        $saturationPoint = $this->CalcSaturationPoint($temperature);
        
        return number_format( ($waterVapor / $saturationPoint * 100 ), 3, '.', '' );
    }


    private function CalcNewWeather(Location $location)
    {
        $clouds = $location->GetClouds();
        $temperature = $location->GetTemperature();
        
        $relativeHumidity = $this->CalcRelativeHumidity($location);

        if($relativeHumidity >= 100)
        {
            // rain or something
        }
        else
        {
            // else
        }        
    }

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

    private function SetLocationAdjustment($locationType)
    {
        switch($locationType)
        {
            case 1:
                break;
            case 2:
                break;
            case 3:
                break;
            case 4:                
                break;
            case 5:
                break;
            case 6:
                break;
            case 7:
                break;
            case 8:
                break;
            case 9:
                break;
            case 10:
                break;
            case 11:
                break;
        }
    }

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
}

?>
