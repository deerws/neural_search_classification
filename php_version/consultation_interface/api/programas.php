<?php
header('Content-Type: application/json');
// Simulação - substitua pela sua lógica de banco de dados/CSV
$programas = ["Programa A", "Programa B", "Programa C"];
echo json_encode($programas);
?>