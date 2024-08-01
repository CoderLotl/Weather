<?php
require('autoloader.php');

//////////////////////////
/* NOTES */
{
    /*
    WATER:

    15 -> DESERT
    15 -> CANYON
    50 -> MOUNTAINS
    30 -> TUNDRA
    100 -> PLAINS / MEADOWS / TAIGA
    200 -> WOODS / FOREST
    1000 -> SWAMPS / LAKE
    250 -> JUNGLES
    */
}


// - - - - - - - - - - -
// - - - [ TEST ] - - - * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
// - - - - - - - - - - -

$weatherMachine = new WeatherMachine();

//$weatherMachine->RunDays(1); // Runs 1 full day (8 day stages). Example: from noon of day 1 to noon of day 2.
//$weatherMachine->RunTillEndOfDay(1); // Runs from whatever point of the day currently is to the end of the day.
//$weatherMachine->RunSingleDayStage(1); // Runs a single day stage.