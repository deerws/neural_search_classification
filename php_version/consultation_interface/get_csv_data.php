<?php
header('Content-Type: application/json');

$csvFile = 'final_aprovações_BACKUP.csv';

if (!file_exists($csvFile)) {
    echo json_encode(['error' => 'Arquivo CSV não encontrado: ' . $csvFile]);
    exit;
}

// Lê o arquivo CSV e converte para UTF-8 (resolve problemas de encoding)
$csvData = file_get_contents($csvFile);
$csvData = mb_convert_encoding($csvData, 'UTF-8', 'auto'); // Força UTF-8

// Processa as linhas do CSV
$lines = explode("\n", $csvData);
$headers = str_getcsv(array_shift($lines), ';'); // Usa ";" como delimitador
$data = [];

foreach ($lines as $line) {
    if (empty(trim($line))) continue;
    $row = str_getcsv($line, ';');
    if (count($row) === count($headers)) {
        $data[] = array_combine($headers, $row);
    }
}

echo json_encode($data);
?>