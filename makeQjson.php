<?php

/*
 * CSV: name,guid,car_model,skin,ballast,laps...
 * where a lap is either total lap time, or sectors split by |
 * file should be ordered by finishing positions
 */

$qually = [
    'TrackName' => 'ks_silverstone',
    'TrackConfig' => 'international',
    'Type' => 'QUALIFY',
    'DurationSecs' => 0,
    'RaceLaps' => 0,
    'Cars' => [],
    'Result' => [],
    'Laps' => [],
];

$f = fopen('qual.csv', 'r');

$id = 0;

while (($csvLine = fgetcsv($f))) {
    
    $qually['Cars'][] = [
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

    // Initialise bestlap to something large so we can use min()
    $bestLap = 999999;
    
    // Step through fields 5...end which contain the laps
    for ($i = 5; $i < count($csvLine); $i++)
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
            $qually['Laps'][] = [
                'DriverName' => $csvLine[0],
                'DriverGuid' => $csvLine[1],
                'CarId' => $id,
                'CarModel' => $csvLine[2],
                'Timestamp' => 0,
                'LapTime' => array_sum($sectors),
                'Sectors' => $sectors,
                'Cuts' => 0,
                'BallastKG' => (int) $csvLine[4],
            ];
            $bestLap = min($bestLap, array_sum($sectors));
        }
    }
    
    // Add the result now we know their best lap time
    $qually['Result'][] = [
        'DriverName' => $csvLine[0],
        'DriverGuid' => $csvLine[1],
        'CarId' => $id,
        'CarModel' => $csvLine[2],
        'BestLap' => $bestLap,
        'TotalTime' => 0,
        'BallastKG' => $csvLine[4],
    ];
    
    $id++;
}
fclose($f);

echo json_encode($qually);
