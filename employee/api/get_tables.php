<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$tablesFile = '../config/tables.json';

if (file_exists($tablesFile)) {
    $tablesData = file_get_contents($tablesFile);
    echo $tablesData;
} else {
    $tables = array();

    $locations = ['Window Side', 'Center', 'Corner', 'Garden View', 'Balcony', 'Terrace', 'Private Section', 'Family Section', 'VIP Section'];
    $types = ['Regular', 'Family', 'VIP'];
    $capacities = [2, 4, 6, 8, 10];

    for ($i = 1; $i <= 20; $i++) {
        $tables[] = array(
            'id' => $i,
            'name' => "Table $i",
            'capacity' => $capacities[array_rand($capacities)],
            'type' => $types[array_rand($types)],
            'location' => $locations[array_rand($locations)]
        );
    }

    $response = array('tables' => $tables);
    echo json_encode($response, JSON_PRETTY_PRINT);
}
