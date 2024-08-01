<?php

/////////////////////////////////////////////////////////////
// - - - - - SEASON CONTROL CONFIG
define('cycle', 168); // The cycle represents the amount of days a whole cycle has. The number MUST be divisible by 4.
define('season_length', (cycle / 4)); // The length of a current season, which is 1/4 of the year cycle.
define('time_divider', (season_length / 7));
define('seasons',
    [
        // index 0 = start of the season. index 1 = end of the season.
        'Spring' => [((cycle / 2 / -4) +1 ), (cycle / 2 / 4)],
        'Summer' => [((cycle / 2 / 4) +1), (cycle / 2 / 4)],
        'Fall' => [((cycle / 2 / 4) -1), (cycle / 2 / -4)],
        'Winter' => [((cycle / 2 / -4) -1), (cycle / 2 / -4)]
    ]
);

/////////////////////////////////////////////////////////////
// - - - - - CONTROL OF CONSTANT VARIABLES - - -
// IDS: 1 [plains, meadows], 2 [jungles], 3 [woods, forests], 4 [deserts], 5 [mountains], 6 [swamps], 7 [canyons], 8 [lake], 9 [taiga], 10 [tundra], 11 [tundra deep]

// Machine MAIN Control
define('applyTemperature', true); // If set to false, no temperature calculation will be executed (and therefore no weather too).
define('applyWeather', true); // If set to false, no weather calculations will be executed, so the weather will stay the same.

// Chances Control
define('blowingWindChances', 35); // The chances of some 'wind' actually returning some water to the ground without actual rain.
define('blowingWindReturn', 35); // The amount of water returned to the ground by 'some means'. The system doesn't contemplate the exitence of wind, but water has to return somehow and clouds have to go sometimes.
define('dewFactor', 35); // The percentage of water vapor that returns back to the ground in the form of dew.
define('precipitationFactor', 35); // Similar to the above, this controls the percentage of water returned to ground by the rain.

// Dew Control
// Types of locations: 1: plains/meadows. 2: jungle. 3: woods/forest. 4: desert. 5: mountains. 6: swamp. 7: canyon. 8: lake. 9: taiga. 10: tundra. 11: tundra (deep)
define('lowDewTypes', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]); // The types of locations which have low atmosphere dew.
define('highDewTypes', [4, 7]); // The types of locations which have cloud dew.

// Rain and Wind Control
define('windAndRainCloudReduction', true); // If true, clouds are going to get reduced both by rain and by some kind of wind, returning water to the grund.
define('firstOrder', 1); // 1 = wind. 2 = rain.
define('placesWithNoRain',
    [
        4,
        7
    ]
); // The location types id of those places where you don't want it to rain.
define('placesWithNoWind',
    [
        1, // plains/meadows
        2, // jungle
        3, // woods/forest
        5, // mountains
        6, // swamp
        8, // lake
        9, // taiga
        10, // tundra
        11 // tundra (deep)
    ]
); // The location types id of those places where you don't want it to be any wind.

// Water Checking
define('waterLimits',
    [
        '1'=>100, // plains/meadows
        '2'=>250, // jungle
        '3'=>200, // woods/forest
        '4'=>15, // desert
        '5'=>50, // mountains
        '6'=>1000, // swamp
        '7'=>15, // canyon
        '8'=>1000, // lake
        '9'=>100, // taiga
        '10'=>30, // tundra
        '11'=>30 // tundra (deep)
    ]
);
// These are the water limits for each region, ideally measured as grams of water per cubic meter.

// WATER LIMITS REFERENCE
/*15 -> DESERT
15 -> CANYON
50 -> MOUNTAINS
30 -> TUNDRA
100 -> PLAINS / MEADOWS / TAIGA
200 -> WOODS / FOREST
1000 -> SWAMPS / LAKE
250 -> JUNGLES*/

/////////////////////////////////////////////////////////////

define('daystage',
    [
        'midnight' => 0,
        'night' => 1,                        
        'dawn' => 2,
        'morning' => 3,
        'midday' => 4,
        'afternoon' => 5,
        'evening' => 6,
        'dusk' => 7
    ]);