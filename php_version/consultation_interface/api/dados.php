<?php
header('Content-Type: application/json');
$filtroPrograma = $_GET['programa'] ?? '';

// Simulação - substitua pela sua lógica real
$dados = [
  ["Programa" => "Programa A", "Área" => "Área 1", "value" => 10],
  ["Programa" => "Programa B", "Área" => "Área 2", "value" => 20]
];

// Filtra os dados se um programa for selecionado
if (!empty($filtroPrograma)) {
  $dados = array_filter($dados, fn($item) => $item['Programa'] === $filtroPrograma);
}

echo json_encode(array_values($dados));
?>