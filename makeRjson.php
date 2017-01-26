<?php

/*
 * CSV: name,guid,car_model,skin,ballast,total_time,laps...
 * where a lap is either total lap time, or sectors split by |
 * file should be ordered by finishing positions
 */

$race = [
    'TrackName' => 'algarve_international_circuit',
    'TrackConfig' => '',
    'Type' => 'RACE',
    'DurationSecs' => 0,
    'RaceLaps' => 0,
    'Cars' => [],
    'Result' => [],
    'Laps' => [],
];

$f = fopen('race.csv', 'r');

$id = 0;

while (($csvLine = fgetcsv($f))) {
    // Convert ballast to an int (?)
    $csvLine[4] = (int) $csvLine[4];

    // Count laps for RaceLaps
    $laps = 0;

    $race['Cars'][] = [
        'CarId' => $id,
        'Driver' => [
            'Name' => $csvLine[0],
            'Team' => '',
            'Guid' => $csvLine[1],
        ],
        'Model' => $csvLine[2],
        'Skin' => $csvLine[3],
        'BallastKG' => $csvLine[4],
    ];

    $timestamp = 0;
    // Initialise bestlap to something large so we can use min()
    $bestLap = 999999;

    // Step through fields 6...end which contain the laps
    for ($i = 6; $i < count($csvLine); $i++)
    {
        if ($csvLine[$i]) {

            if (strpos($csvLine[$i], '|') !== false) {
                // it's an array of sectors
                $sectors = explode('|', $csvLine[$i]);
            } else {
                // it's just a laptime
                $sectors = [
                    (int) $csvLine[$i]
                ];
            }

            $timestamp += array_sum($sectors);
            $race['Laps'][] = [
                'DriverName' => $csvLine[0],
                'DriverGuid' => $csvLine[1],
                'CarId' => $id,
                'CarModel' => $csvLine[2],
                'Timestamp' => $timestamp,
                'LapTime' => array_sum($sectors),
                'Sectors' => $sectors,
                'Cuts' => 0,
                'BallastKG' => (int) $csvLine[4],
            ];
            $bestLap = min($bestLap, array_sum($sectors));
            $laps++;
        }
    }

    // Add the driver's result now we know their best lap
    $race['Result'][] = [
        'DriverName' => $csvLine[0],
        'DriverGuid' => $csvLine[1],
        'CarId' => $id,
        'CarModel' => $csvLine[2],
        'BestLap' => $bestLap,
        'TotalTime' => (int) $csvLine[5],
        'BallastKG' => $csvLine[4],
    ];

    // Update the race laps appropriately
    $race['RaceLaps'] = max($race['RaceLaps'], $laps);

    // Next car!
    $id++;
}
fclose($f);

// sort $race['Laps'] by Timestamp
usort($race['Laps'], function($a, $b) {
    return $a['Timestamp'] - $b['Timestamp'];
});

echo json_encode($race);
