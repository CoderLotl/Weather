<?php

class TemperatureParameters // This is a package-class, used only to store and move data.
{
    private $tuning;
    private $amplitude;
    private $plus;
    private $topLimits;
    private $bottomLimits;

    public function __construct(float $tuning, float $amplitude, int $plus, array $topLimits, array $bottomLimits)
    {
        $this->tuning = $tuning;
        $this->amplitude = $amplitude;
        $this->plus = $plus;
        $this->topLimits = $topLimits;
        $this->bottomLimits = $bottomLimits;        
    }

    public function GetTunning()
    {
        return $this->tuning;
    }
    public function GetAmplitude()
    {
        return $this->amplitude;
    }
    public function GetPlus()
    {
        return $this->plus;
    }
    public function GetTopLimits($index)
    {
        return $this->topLimits[$index];
    }
    public function GetBottomLimits($index)
    {
        return $this->bottomLimits[$index];
    }

}
?>