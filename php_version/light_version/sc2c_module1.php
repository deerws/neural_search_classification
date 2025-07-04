<?php
header('Content-Type: text/html; charset=UTF-8'); // Define o charset HTTP (obrigatório)
mb_internal_encoding('UTF-8');                    // Garante que o PHP processe strings como UTF-8
ini_set('default_charset', 'UTF-8'); 

// Função para carregar a hierarquia de domínios
function loadDomainHierarchy($program) {
    $file = $program === 'ACARE' ? 'taxonomy.txt' : 'nasa_taxonomy.txt';
    $hierarchy = [];
    $currentDomain = '';
    
    foreach (file($file) as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Detecta se é um domínio (1.Chemical Propulsion)
        if (preg_match('/^(\d+)\.([^0-9].*)/', $line, $matches)) {
            $currentDomain = $matches[1] . '.' . $matches[2];
            $hierarchy[$currentDomain] = [];
        }
        // Detecta subdomínios (1.1.Integrated Systems...)
        elseif (preg_match('/^(\d+\.\d+)\.(.*)/', $line, $matches)) {
            $hierarchy[$currentDomain][$matches[1]] = $matches[2];
        }
    }
    return $hierarchy;
}

$acareHierarchy = loadDomainHierarchy('ACARE');
$nasaHierarchy = loadDomainHierarchy('NASA');

// Função para carregar e processar o CSV
function loadCSVData($filename) {
    if (!file_exists($filename)) {
        return array();
    }
    
    $csvContent = file_get_contents($filename);
    $lines = explode("\n", $csvContent);
    $headers = str_getcsv(array_shift($lines));
    $data = array();
    
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        $row = array_combine($headers, str_getcsv($line));
        $data[] = $row;
    }
    
    return $data;
}

// Função para parsear as classificações
function parseClassification($str) {
    if (empty($str)) return null;
    
    // Padrão: "código.descrição (score)"
    if (preg_match('/^([\dA.]+)\.(.*?)\s+\(([\d.]+)\)$/', $str, $matches)) {
        return array(
            'code' => $matches[1],
            'description' => trim($matches[2]),
            'score' => (float)$matches[3]
        );
    }
    
    return null;
}

// Função de comparação para ordenação
function compareScores($a, $b) {
    if ($a['score'] == $b['score']) {
        return 0;
    }
    return ($a['score'] < $b['score']) ? 1 : -1;
}

// Função para formatar classificação para exibição
function formatClassificationForDisplay($classification) {
    if (!$classification || $classification['code'] === 'N/A') {
        return 'Sem classificação';
    }
    
    return $classification['code'] . ' - ' . $classification['description'] . ' (Score: ' . $classification['score'] . ')';
}
function generateConsolidatedCSV($data, $approvals, $feedbacks, $manualSubdomains, $userName) {
    $csvContent = [];
    
    // Cabeçalho do CSV
    $header = [
        'Programa',
        'Área',
        'Linha de Pesquisa',
        'Usuário',
        'ACARE - Melhor Classificação',
        'ACARE - Score',
        'ACARE - Todas as Classificações',
        'NASA - Melhor Classificação',
        'NASA - Score',
        'NASA - Todas as Classificações',
        'Comentários',
        'Status de Aprovação',
        'Data de Processamento'
    ];
    $csvContent[] = $header;
    
    foreach ($data as $rowIndex => $row) {
        // Processa ACARE
        $acareTop = !empty($row['ACARE']) ? $row['ACARE'][0] : ['code' => 'N/A', 'description' => 'Sem classificação', 'score' => 0];
        $acareAll = [];
        
        // Verifica se há subdomínios manuais para ACARE
        if (isset($manualSubdomains[$rowIndex]['ACARE'])) {
            foreach ($manualSubdomains[$rowIndex]['ACARE'] as $manual) {
                $acareAll[] = $manual['code'] . ' (' . $manual['score'] . ')';
                // Se for a primeira classificação manual, substitui a top
                if (empty($acareAll)) {
                    $acareTop = $manual;
                }
            }
        } else {
            foreach ($row['ACARE'] as $classification) {
                if ($classification['code'] !== 'N/A') {
                    $acareAll[] = $classification['code'] . ' (' . $classification['score'] . ')';
                }
            }
        }
        
        // Processa NASA
        $nasaTop = !empty($row['NASA']) ? $row['NASA'][0] : ['code' => 'N/A', 'description' => 'Sem classificação', 'score' => 0];
        $nasaAll = [];
        
        // Verifica se há subdomínios manuais para NASA
        if (isset($manualSubdomains[$rowIndex]['NASA'])) {
            foreach ($manualSubdomains[$rowIndex]['NASA'] as $manual) {
                $nasaAll[] = $manual['code'] . ' (' . $manual['score'] . ')';
                // Se for a primeira classificação manual, substitui a top
                if (empty($nasaAll)) {
                    $nasaTop = $manual;
                }
            }
        } else {
            foreach ($row['NASA'] as $classification) {
                if ($classification['code'] !== 'N/A') {
                    $nasaAll[] = $classification['code'] . ' (' . $classification['score'] . ')';
                }
            }
        }
        
        // Determina status de aprovação
        $status = 'Não Aprovado';
        if ($acareTop['score'] >= 0.7 || $nasaTop['score'] >= 0.7) {
            $status = 'Aprovado';
        } elseif ($acareTop['score'] >= 0.5 || $nasaTop['score'] >= 0.5) {
            $status = 'Em Análise';
        }
        
        // Verifica se há aprovações manuais que podem alterar o status
        $hasApproved = false;
        for ($i = 0; $i < 3; $i++) {
            $acareKey = $rowIndex . '-acare-' . $i;
            $nasaKey = $rowIndex . '-nasa-' . $i;
            
            if (isset($approvals[$acareKey])) {
                $status = $approvals[$acareKey] === 'Approved' ? 'Aprovado' : 'Não Aprovado';
                $hasApproved = true;
                break;
            }
            
            if (isset($approvals[$nasaKey])) {
                $status = $approvals[$nasaKey] === 'Approved' ? 'Aprovado' : 'Não Aprovado';
                $hasApproved = true;
                break;
            }
        }
        
        // Se houver subdomínios manuais, considera como aprovado
        if (!$hasApproved && (isset($manualSubdomains[$rowIndex]['ACARE']) || isset($manualSubdomains[$rowIndex]['NASA']))) {
            $status = 'Aprovado';
        }
        
        // Formata as classificações
        $acareTopFormatted = $acareTop['code'] !== 'N/A' ? 
            $acareTop['code'] . ' - ' . $acareTop['description'] . ' (Score: ' . $acareTop['score'] . ')' : 
            'Sem classificação';
        
        $nasaTopFormatted = $nasaTop['code'] !== 'N/A' ? 
            $nasaTop['code'] . ' - ' . $nasaTop['description'] . ' (Score: ' . $nasaTop['score'] . ')' : 
            'Sem classificação';
        
        $acareAllFormatted = !empty($acareAll) ? implode('; ', $acareAll) : 'Sem classificação';
        $nasaAllFormatted = !empty($nasaAll) ? implode('; ', $nasaAll) : 'Sem classificação';
        
        // Adiciona a linha ao CSV
        $csvContent[] = [
            $row['Programa'],
            $row['Área'],
            $row['Linha_de_Pesquisa'],
            $userName,
            $acareTopFormatted,
            $acareTop['score'],
            $acareAllFormatted,
            $nasaTopFormatted,
            $nasaTop['score'],
            $nasaAllFormatted,
            isset($feedbacks[$rowIndex]) ? implode('; ', $feedbacks[$rowIndex]) : '',
            $status,
            date('d/m/Y H:i:s')
        ];
    }
    
    return $csvContent;
}
// Função para gerar CSV palatável
function generateUserFriendlyCSV($data, $filters = array()) {
    $filteredData = $data;
    
    // Aplica filtros se especificados
    if (!empty($filters['programa'])) {
        $filteredData = array_filter($filteredData, function($row) use ($filters) {
            return $row['Programa'] === $filters['programa'];
        });
    }
    
    if (!empty($filters['area'])) {
        $filteredData = array_filter($filteredData, function($row) use ($filters) {
            return stripos($row['Área'], $filters['area']) !== false;
        });
    }
    
    // Cabeçalhos do CSV palatável
    $headers = array(
        'Programa',
        'Área',
        'Linha de Pesquisa',
        'ACARE - Melhor Classificação',
        'ACARE - Score',
        'ACARE - Todas as Classificações',
        'NASA - Melhor Classificação',
        'NASA - Score',
        'NASA - Todas as Classificações',
        'Comentários',
        'Status de Aprovação',
        'Data de Processamento'
    );
    
    $csvContent = array();
    $csvContent[] = $headers;
    
    foreach ($filteredData as $row) {
        // ACARE - melhor classificação (primeira após ordenação)
        $acareTop = !empty($row['ACARE']) ? $row['ACARE'][0] : array('code' => 'N/A', 'description' => 'Sem classificação', 'score' => 0);
        $acareTopFormatted = formatClassificationForDisplay($acareTop);
        $acareScore = $acareTop['score'];
        
        // ACARE - todas as classificações
        $acareAll = array();
        foreach ($row['ACARE'] as $classification) {
            if ($classification['code'] !== 'N/A') {
                $acareAll[] = $classification['code'] . ' (' . $classification['score'] . ')';
            }
        }
        $acareAllFormatted = !empty($acareAll) ? implode('; ', $acareAll) : 'Sem classificação';
        
        // NASA - melhor classificação
        $nasaTop = !empty($row['NASA']) ? $row['NASA'][0] : array('code' => 'N/A', 'description' => 'Sem classificação', 'score' => 0);
        $nasaTopFormatted = formatClassificationForDisplay($nasaTop);
        $nasaScore = $nasaTop['score'];
        
        // NASA - todas as classificações
        $nasaAll = array();
        foreach ($row['NASA'] as $classification) {
            if ($classification['code'] !== 'N/A') {
                $nasaAll[] = $classification['code'] . ' (' . $classification['score'] . ')';
            }
        }
        $nasaAllFormatted = !empty($nasaAll) ? implode('; ', $nasaAll) : 'Sem classificação';
        
        // Determina status de aprovação baseado nos scores
        $status = 'Não Aprovado';
        if ($acareScore >= 0.7 || $nasaScore >= 0.7) {
            $status = 'Aprovado';
        } elseif ($acareScore >= 0.5 || $nasaScore >= 0.5) {
            $status = 'Em Análise';
        }
        
        $csvRow = array(
            $row['Programa'],
            $row['Área'],
            $row['Linha_de_Pesquisa'],
            $acareTopFormatted,
            $acareScore,
            $acareAllFormatted,
            $nasaTopFormatted,
            $nasaScore,
            $nasaAllFormatted,
            $row['Comentário'],
            $status,
            date('d/m/Y H:i:s')
        );
        
        $csvContent[] = $csvRow;
    }
    
    return $csvContent;
}

// Função para salvar CSV
function saveCSV($data, $filename) {
    $fp = fopen($filename, 'w');
    
    // Adiciona BOM para UTF-8 (ajuda com acentos no Excel)
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    foreach ($data as $row) {
        fputcsv($fp, $row, ';'); // Usando ponto e vírgula como separador
    }
    
    fclose($fp);
    return true;
}

// Função para gerar estatísticas
function generateStatistics($data) {
    $stats = array(
        'total' => count($data),
        'aprovados' => 0,
        'em_analise' => 0,
        'nao_aprovados' => 0,
        'por_programa' => array(),
        'scores_acare' => array(),
        'scores_nasa' => array()
    );
    
    foreach ($data as $row) {
        // Contabiliza por programa
        $programa = $row['Programa'];
        if (!isset($stats['por_programa'][$programa])) {
            $stats['por_programa'][$programa] = 0;
        }
        $stats['por_programa'][$programa]++;
        
        // Analisa scores
        $acareScore = !empty($row['ACARE']) ? $row['ACARE'][0]['score'] : 0;
        $nasaScore = !empty($row['NASA']) ? $row['NASA'][0]['score'] : 0;
        
        $stats['scores_acare'][] = $acareScore;
        $stats['scores_nasa'][] = $nasaScore;
        
        // Determina status
        if ($acareScore >= 0.7 || $nasaScore >= 0.7) {
            $stats['aprovados']++;
        } elseif ($acareScore >= 0.5 || $nasaScore >= 0.5) {
            $stats['em_analise']++;
        } else {
            $stats['nao_aprovados']++;
        }
    }
    
    return $stats;
}

// Processamento principal
try {
    // Carrega os dados do CSV original
    $csvData = loadCSVData('resultados_formatado.csv');
    
    if (empty($csvData)) {
        throw new Exception('Arquivo CSV não encontrado ou vazio.');
    }
    
    // Processa os dados
    $processedData = array();
    foreach ($csvData as $row) {
        // Processa ACARE
        $acare = array();
        for ($i = 1; $i <= 3; $i++) {
            $key = 'ACARE ' . $i;
            if (isset($row[$key])) {
                $classification = parseClassification($row[$key]);
                if ($classification) {
                    $acare[] = $classification;
                }
            }
        }
        
        if (empty($acare)) {
            $acare[] = array('code' => 'N/A', 'description' => 'Sem classificação', 'score' => 0);
        }
        
        usort($acare, 'compareScores');
        
        // Processa NASA
        $nasa = array();
        for ($i = 1; $i <= 3; $i++) {
            $key = 'NASA ' . $i;
            if (isset($row[$key])) {
                $classification = parseClassification($row[$key]);
                if ($classification) {
                    $nasa[] = $classification;
                }
            }
        }
        
        if (empty($nasa)) {
            $nasa[] = array('code' => 'N/A', 'description' => 'Sem classificação', 'score' => 0);
        }
        
        usort($nasa, 'compareScores');
        
        $processedData[] = array(
            'Programa' => isset($row['Programa']) ? $row['Programa'] : '',
            'Área' => isset($row['Área']) ? $row['Área'] : '',
            'Linha_de_Pesquisa' => isset($row['Linha de Pesquisa']) ? $row['Linha de Pesquisa'] : '',
            'ACARE' => $acare,
            'NASA' => $nasa,
            'Comentário' => isset($row['Comentário']) ? $row['Comentário'] : ''
        );
    }
    
    // Processa requisições POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $filters = array();
        
        if (!empty($_POST['programa'])) {
            $filters['programa'] = $_POST['programa'];
        }
        
        if (!empty($_POST['area'])) {
            $filters['area'] = $_POST['area'];
        }
        
        // Verifica se é para baixar o CSV consolidado
        if (isset($_POST['download_consolidated'])) {
            $userName = isset($_POST['user_name']) ? $_POST['user_name'] : 'Usuario';
            
            // Decodifica os dados JSON
            $approvals = isset($_POST['approvals']) ? json_decode($_POST['approvals'], true) : array();
            $feedbacks = isset($_POST['feedbacks']) ? json_decode($_POST['feedbacks'], true) : array();
            $manualSubdomains = isset($_POST['manual_subdomains']) ? json_decode($_POST['manual_subdomains'], true) : array();
            
            $consolidatedData = generateConsolidatedCSV($processedData, $approvals, $feedbacks, $manualSubdomains, $userName);
            
            $filename = 'aprovacoes_consolidadas_' . date('Y-m-d_H-i-s') . '.csv';
            
            // Cria o arquivo CSV
            $fp = fopen('php://output', 'w');
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            // Adiciona BOM para UTF-8
            fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            foreach ($consolidatedData as $row) {
                fputcsv($fp, $row, ';');
            }
            
            fclose($fp);
            exit;
        }        
        // Gera CSV palatável (original)
        $userFriendlyData = generateUserFriendlyCSV($processedData, $filters);
    
        $filename = 'aprovacoes_' . date('Y-m-d_H-i-s') . '.csv';
        saveCSV($userFriendlyData, $filename);
        
        // Download do arquivo
        header('Content-Type: application/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filename));
        
        readfile($filename);
        unlink($filename);
        exit;
    }
    // Gera lista de programas para o dropdown
    $programas = array();
    foreach ($processedData as $row) {
        $programa = $row['Programa'];
        if (!in_array($programa, $programas)) {
            $programas[] = $programa;
        }
    }
    sort($programas);
    
    // Gera estatísticas
    $stats = generateStatistics($processedData);

} catch (Exception $e) {
$error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Versão alternativa para compatibilidade -->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SC2C.Aero - Módulo de Aprovação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Tema Escuro Uniforme - SC2C.Aero */
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            padding: 20px;
        }
        .logo-title-container {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .buttons-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 20px 0;
        }
        .title-container h1 {
            font-size: 28px;
            margin-bottom: 5px;
            color: #4a86e8;
        }

        .subtitle {
            font-style: italic;
            font-size: 18px;
            color: #4A86E8;
            margin-top: 0;
        }
        
        
        .header-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        .header-logo {
            width: 200px; /* Aumentado de 50px */
            height: 200px; /* Aumentado de 50px */
        }
        
        
        .download-buttons, .manual-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        
        .container {
            max-width: none; /* Remove a limitação de 1800px */
            width: 98%; /* Usa 98% da tela (deixa margem mínima) */
            margin: 0 auto;
            padding: 0 10px;
        }
        
        .panel {
            background-color: #1e1e1e;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #333;
        }
        
        .table-container {
            overflow-x: visible; /* Elimina a rolagem horizontal */
            overflow-y: auto; /* Mantém a rolagem vertical */
            max-height: 75vh;
            width: 100%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #252525;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        thead th {
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: #1a3b6a;
            color: white;
        }
        
        th, td {
            padding: 12px 15px;
            border: 1px solid #252525;
            text-align: left;
            background-color: #252525;
        }
        
        th {
            background-color: #2a3f5f;
            color: white;
            font-weight: 600;
        }
        
        tr {
            background-color: #252525;
        }
        
        tr:hover {
            background-color: #2d2d2d;
        }
        
        .classification {
            margin-bottom: 10px;
            padding: 12px;
            border: 1px solid #444;
            border-radius: 6px;
            background-color: #2a2a2a;
            position: relative;
        }
        
        .approved {
            background-color: #1a3a1a !important;
        }
        
        .rejected {
            background-color: #3a1a1a !important;
        }
        
        .chart-container {
            width: 100%;
            height: 100px;
            margin-top: 10px;
        }
        
        /* Botões */
        .btn-approve {
            background-color: #388e3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 5px;
        }
        
        .btn-reject {
            background-color: #d32f2f;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        
        .btn-approve:hover, .btn-reject:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn-default {
            background-color: #5a6268;
            color: white;
        }
        
        .btn-primary {
            background-color: #1976d2;
            color: white;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            margin: 0 5px 10px;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.85;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        /* Formulários */
        .form-control {
            background-color: #333;
            color: white;
            border: 1px solid #444;
            border-radius: 4px;
            padding: 10px;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .form-control:focus {
            border-color: #1976d2;
            outline: none;
            box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.3);
        }
        
        /* Layout */
        .text-center {
            text-align: center;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #bbbbbb;
            font-weight: 500;
        }
        
        /* Títulos */
        h1, h2, h3 {
            color: #e0e0e0;
            margin-top: 0;
        }
        
        h1 {
            font-size: 28px;
            margin-bottom: 25px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            color: #4a86e8;
        }
        
        /* Painéis */
        .panel-heading {
            background-color: #2a3f5f;
            color: white;
            padding: 12px 20px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            font-weight: 600;
            font-size: 18px;
        }
        
        .panel-body {
            padding: 20px;
            background-color: #252525;
            border-bottom-left-radius: 6px;
            border-bottom-right-radius: 6px;
        }
        
        .panel-default {
            border: 1px solid #444;
            border-radius: 6px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        /* Listas */
        ul {
            padding-left: 20px;
            margin-bottom: 0;
        }
        
        li {
            margin-bottom: 8px;
            color: #d0d0d0;
        }
        
        /* Botões pequenos */
        .btn-xs {
            padding: 5px 10px;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .btn-feedback, .btn-add-subdomain {
            margin-top: 8px;
            width: 100%;
            padding: 8px 12px;
            font-size: 13px;
        }
        
        /* Barra de rolagem */
        .table-container::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #2a2a2a;
            border-radius: 10px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #4a4a4a;
            border-radius: 10px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #5a5a5a;
        }
        
        /* Transições */
        * {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Cabeçalho fixo */
        thead th {
            transition: background-color 0.3s ease;
        }
        
        thead th:hover {
            background-color: #1f3250;
        }
        
        /* Modais */
        .modal-content {
            background-color: #252525;
            color: #e0e0e0;
            border: 1px solid #444;
        }
        
        .modal-header, .modal-footer {
            border-color: #444;
        }
        
        .close {
            color: #e0e0e0;
            opacity: 0.8;
        }
        
        .nav-tabs > li.active > a {
            background-color: #252525;
            color: #e0e0e0;
            border: 1px solid #444;
            border-bottom-color: transparent;
        }
        
        .nav-tabs > li > a {
            color: #bbbbbb;
        }
        
        /* Estilo para o modal de subdomínio */
        .subdomain-item {
            padding: 8px;
            margin: 5px 0;
            background-color: #333;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .subdomain-item:hover {
            background-color: #3a3a3a;
        }
        
        .subdomain-item input[type="radio"] {
            margin-right: 8px;
        }
        
        /* Estilo para os domínios expandíveis */
        .domain-toggle {
            cursor: pointer;
            display: block;
            width: 90%;
            padding: 10px 20px;
            margin: 5px 0;
            background-color: rgba(56,104,145);
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-align: left;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-clear-selections {
            width: 100%;
            margin-top: 5px;
        }
        
        .domain-toggle:hover {
            background-color: #0056b3;
        }
        @media (max-width: 768px) {
            .logo-title-container {
                flex-direction: column;
                text-align: center;
            }
            
            .download-buttons, .manual-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .download-buttons .btn, 
            .manual-buttons .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        
        .subdomain-container {
            display: none;
            flex-direction: column;
            padding-left: 20px;

        }
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-container">
            <div class="logo-title-container">
                <img src="Logotipo_SC2C_Vertical-512x512.png" class="header-logo" alt="Logo Rede UFSC"/>
                <div class="title-container">
                    <h1>Rede UFSC em Aeronáutica e Espaço</h1>
                    <h2 class="subtitle">Módulo de Aprovação</h2>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="userName">Seu Nome:</label>
                        <input type="text" class="form-control" id="userName" placeholder="Digite seu nome">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="programFilter">Filtrar por Programa:</label>
                        <select class="form-control" id="programFilter">
                            <option value="">Todos os Programas</option>
                            <?php foreach ($programas as $programa): ?>
                                <option value="<?php echo htmlspecialchars($programa); ?>">
                                    <?php echo htmlspecialchars($programa); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="buttons-container">
                <div class="download-buttons">
                    <button id="downloadBtn" class="btn btn-primary">Baixar CSV Atualizado</button>
                    <button id="downloadExcelBtn" class="btn btn-success">Baixar Excel com Aprovações</button>
                </div>
                <div class="manual-buttons">
                    <a href="https://sc2c.ufsc.br/aero/taxonomy/" target="_blank" class="btn btn-info">Manual SC2C</a>
                    <a href="https://www.daccampania.com/wp-content/uploads/2022/01/ACARE_Taxonomy.pdf" target="_blank" class="btn btn-info">Manual ACARE</a>
                    <a href="https://www3.nasa.gov/sites/default/files/atoms/files/2020_nasa_technology_taxonomy.pdf" target="_blank" class="btn btn-info">Manual NASA</a>
                </div>
            </div>
        </div>
    </div>

        
        <div class="panel">
            <h3>Instruções de Uso</h3>
            <div class="row">
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">1. Informações Básicas</div>
                        <div class="panel-body">
                            <ul>
                                <li>Digite seu nome no campo indicado</li>
                                <li>Selecione um programa para filtrar (opcional) e atenha-se a sua área de atuação</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">2. Classificação</div>
                        <div class="panel-body">
                            <ul>
                                <li>Analise as três classificações sugeridas para ACARE e NASA</li>
                                <li>Aprove ou rejeite cada subdomínio ou abstenha-se</li>
                                <li>Adicione novos subdomínios se necessário</li>
                                <li>Use o botão feedback para enviar um comentário adicional</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">3. Finalização</div>
                        <div class="panel-body">
                            <ul>
                                <li>IMPORTANTE: Baixe o CSV com seus resultados e envie no email: sc2c@contato.ufsc.br</li>
                                <li>Para consulta própria, baixe a versão simplificada em Excel</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="panel table-container">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Área</th>
                        <th>Linha de Pesquisa</th>
                        <th>Classificações ACARE</th>
                        <th>Classificações NASA</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($processedData as $index => $row): ?>
                        <tr data-row="<?php echo $index; ?>" data-program="<?php echo htmlspecialchars($row['Programa']); ?>">
                            <td><?php echo htmlspecialchars($row['Área']); ?></td>
                            <td><?php echo htmlspecialchars($row['Linha_de_Pesquisa']); ?></td>
                            <td>
                                <?php foreach ($row['ACARE'] as $i => $classification): ?>
                                    <div class="classification" data-type="acare" data-index="<?php echo $i; ?>">
                                        <strong><?php echo htmlspecialchars($classification['code']); ?>:</strong> 
                                        <?php echo htmlspecialchars($classification['description']); ?>
                                        (<?php echo number_format($classification['score'], 3); ?>)
                                        <div class="actions" style="margin-top: 5px;">
                                            <button class="btn btn-xs btn-approve">Aprovar</button>
                                            <button class="btn btn-xs btn-reject">Rejeitar</button>
                                        </div>
                                        <?php if ($i === count($row['ACARE']) - 1 && count($row['ACARE']) > 0): ?>
                                            <div class="chart-container">
                                                <canvas id="acareChart-<?php echo $index; ?>"></canvas>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php foreach ($row['NASA'] as $i => $classification): ?>
                                    <div class="classification" data-type="nasa" data-index="<?php echo $i; ?>">
                                        <strong><?php echo htmlspecialchars($classification['code']); ?>:</strong> 
                                        <?php echo htmlspecialchars($classification['description']); ?>
                                        (<?php echo number_format($classification['score'], 3); ?>)
                                        <div class="actions" style="margin-top: 5px;">
                                            <button class="btn btn-xs btn-approve">Aprovar</button>
                                            <button class="btn btn-xs btn-reject">Rejeitar</button>
                                        </div>
                                        <?php if ($i === count($row['NASA']) - 1 && count($row['NASA']) > 0): ?>
                                            <div class="chart-container">
                                                <canvas id="nasaChart-<?php echo $index; ?>"></canvas>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <button class="btn btn-xs btn-default btn-feedback">Feedback</button>
                                <button class="btn btn-xs btn-primary btn-add-subdomain">Adicionar Domínio(Opcional)</button>
                                <button class="btn btn-xs btn-warning btn-clear-selections" style="margin-top: 5px;">Limpar Seleções</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Feedback -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Feedback para Linha <span id="modalRowNumber"></span></h4>
                </div>
                <div class="modal-body">
                    <div id="feedbackList" style="max-height: 200px; overflow-y: auto; margin-bottom: 15px;"></div>
                    <textarea id="newFeedback" class="form-control" placeholder="Adicione seu feedback..." rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="submitFeedback">Enviar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Subdomínio - Modificado para ter o mesmo comportamento de expansão -->

    <div class="modal fade" id="subdomainModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Adicionar Subdomínio</h4>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="programTabs">
                        <li class="active"><a href="#acareTab" data-toggle="tab">ACARE</a></li>
                        <li><a href="#nasaTab" data-toggle="tab">NASA</a></li>
                    </ul>
                    
                    <div class="tab-content">
                        <div class="tab-pane active" id="acareTab">
                            <h4 class="domain-toggle" data-target="acareDomains">Domínios ACARE</h4>
                            <div id="acareDomains" class="subdomain-container">
                                <!-- Domínios serão preenchidos via JavaScript -->
                            </div>
                        </div>
                        <div class="tab-pane" id="nasaTab">
                            <h4 class="domain-toggle" data-target="nasaDomains">Domínios NASA</h4>
                            <div id="nasaDomains" class="subdomain-container">
                                <!-- Domínios serão preenchidos via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="submitSubdomain">Adicionar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@1.12.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        // Estrutura hierárquica de domínios/subdomínios
        var subdomains = {
            ACARE: {
                'FLP': {
                    name: 'Flight Physics (FLP)',
                    subdomains: [
                        { code: 'FLP.1', description: 'Computational Fluid Dynamics' },
                        { code: 'FLP.2', description: 'Unsteady Aerodynamics' },
                        { code: 'FLP.3', description: 'Airflow Control' },
                        { code: 'FLP.4', description: 'High Lift Devices' },
                        { code: 'FLP.5', description: 'Wing Design' },
                        { code: 'FLP.6', description: 'Aerodynamics of External/Removable Items' },
                        { code: 'FLP.7', description: 'Wind Tunnel Testing/Technology' },
                        { code: 'FLP.8', description: 'Wind Tunnel Measuring Techniques' },
                        { code: 'FLP.9', description: 'Computational Acoustics' },
                        { code: 'FLP.10', description: 'External Noise Prediction' }
                    ]
                },
                'AST': {
                    name: 'Aerostructures (AST)',
                    subdomains: [
                        { code: 'AST.1', description: 'Metallic Materials & Basic Processes' },
                        { code: 'AST.2', description: 'Non-Metallic Materials & Basic Processes' },
                        { code: 'AST.3', description: 'Composite Materials & Basic Processes' },
                        { code: 'AST.4', description: 'Advanced Manufacturing Processes & Technologies' },
                        { code: 'AST.5', description: 'Structural Analysis and Design' },
                        { code: 'AST.6', description: 'Aero-elasticity' },
                        { code: 'AST.7', description: 'Buckling, Vibrations, and Acoustics' },
                        { code: 'AST.8', description: 'Smart Materials and Structures' },
                        { code: 'AST.9', description: 'Internal Noise Prediction' },
                        { code: 'AST.10', description: 'Helicopter Aero-acoustics' },
                        { code: 'AST.11', description: 'Noise Reduction' },
                        { code: 'AST.12', description: 'Acoustic Measurements and Test Technology' },
                        { code: 'AST.13', description: 'Aircraft Security' }
                    ]
                },
                'PRO': {
                    name: 'Propulsion (PRO)',
                    subdomains: [
                        { code: 'PRO.1', description: 'Performance' },
                        { code: 'PRO.2', description: 'Turbomachinery/Propulsion Aerodynamics' },
                        { code: 'PRO.3', description: 'Combustion' },
                        { code: 'PRO.4', description: 'Air-breathing Propulsion' },
                        { code: 'PRO.5', description: 'Heat Transfer' },
                        { code: 'PRO.6', description: 'Nozzles, Vectored Thrust, Reheat' },
                        { code: 'PRO.7', description: 'Engine Controls' },
                        { code: 'PRO.8', description: 'Auxiliary Power Unit' },
                        { code: 'PRO.9', description: 'Fuels and Lubricants' },
                        { code: 'PRO.10', description: 'Test Bench Calibration' },
                        { code: 'PRO.11', description: 'Engine Health Monitoring' },
                        { code: 'PRO.12', description: 'Experimental Facilities & Measurement Techniques' },
                        { code: 'PRO.13', description: 'Computational Methods' },
                        { code: 'PRO.14', description: 'Emissions Pollution' },
                        { code: 'PRO.15', description: 'Hydrogen in Aviation' },
                        { code: 'PRO.16', description: 'Hybrid Electric Flight' }
                    ]
                },
                'AVS': {
                    name: 'Aircraft Avionics, Systems & Equipment (AVS)',
                    subdomains: [
                        { code: 'AVS.1', description: 'Avionics' },
                        { code: 'AVS.2', description: 'Cockpit Systems, Visualization & Display Systems' },
                        { code: 'AVS.3', description: 'Navigation/Flight Management/Autoland' },
                        { code: 'AVS.4', description: 'Warning Systems' },
                        { code: 'AVS.5', description: 'Electronics & Microelectronics for On-board Systems' },
                        { code: 'AVS.6', description: 'Sensors Integration' },
                        { code: 'AVS.7', description: 'Flight Data/Recording' },
                        { code: 'AVS.8', description: 'Communications Systems' },
                        { code: 'AVS.9', description: 'Identification' },
                        { code: 'AVS.10', description: 'Avionics Integration' },
                        { code: 'AVS.11', description: 'Optics, Optronics, Lasers, Image Processing' },
                        { code: 'AVS.12', description: 'Electronic Library System' },
                        { code: 'AVS.13', description: 'Aircraft Health & Usage Monitoring System' },
                        { code: 'AVS.14', description: 'Smart Maintenance Systems' },
                        { code: 'AVS.15', description: 'Lighting Systems' },
                        { code: 'AVS.16', description: 'Aircraft Security' },
                        { code: 'AVS.17', description: 'Electrical Power Generation, Distribution & Actuation' },
                        { code: 'AVS.18', description: 'Pneumatic Systems' },
                        { code: 'AVS.19', description: 'Passenger and Freight Systems' },
                        { code: 'AVS.20', description: 'Environmental Control System' },
                        { code: 'AVS.21', description: 'Water and Waste Systems' },
                        { code: 'AVS.22', description: 'Fuel Systems' },
                        { code: 'AVS.23', description: 'Landing Gear and Braking Systems' },
                        { code: 'AVS.24', description: 'Fire Protection Systems' },
                        { code: 'AVS.25', description: 'Hydraulic Systems' }
                    ]
                },
                'FLM': {
                    name: 'Flight Mechanics (FLM)',
                    subdomains: [
                        { code: 'FLM.1', description: 'Open-loop Aircraft Stability Analysis' },
                        { code: 'FLM.2', description: 'Flight Control System' },
                        { code: 'FLM.3', description: 'Aircraft Performance Analysis' },
                        { code: 'FLM.4', description: 'Optimization of Aircraft Performance' },
                        { code: 'FLM.5', description: 'System Failure and Damage Analysis' },
                        { code: 'FLM.6', description: 'Environmental Hazard Analysis' }
                    ]
                },
                'IDV': {
                    name: 'Integrated Design & Validation (IDV)',
                    subdomains: [
                        { code: 'IDV.1', description: 'Methods and IT Tools for Collaborative Engineering' },
                        { code: 'IDV.2', description: 'On-board Systems Engineering' },
                        { code: 'IDV.3', description: 'Environmental and EM Compliance' },
                        { code: 'IDV.4', description: 'Flight/Ground Tests' },
                        { code: 'IDV.5', description: 'Life-cycle Integration' },
                        { code: 'IDV.6', description: 'System Certification' },
                        { code: 'IDV.7', description: 'Fault Tolerant Systems' },
                        { code: 'IDV.8', description: 'Hazard Analysis' },
                        { code: 'IDV.9', description: 'Safety Modelling' },
                        { code: 'IDV.10', description: 'Air Safety Data Analysis' },
                        { code: 'IDV.11', description: 'System Reliability' },
                        { code: 'IDV.12', description: 'Security/Risk Analysis' },
                        { code: 'IDV.13', description: 'Maintenance Modelling' },
                        { code: 'IDV.14', description: 'Infra-red and Radar Signature Control' },
                        { code: 'IDV.15', description: 'Advanced Information Processing' },
                        { code: 'IDV.16', description: 'Collaborative Decision Making' },
                        { code: 'IDV.17', description: 'Simulator Environments & Virtual Reality' },
                        { code: 'IDV.18', description: 'Decision Support Systems' },
                        { code: 'IDV.19', description: 'Information & Knowledge Management' },
                        { code: 'IDV.20', description: 'Autonomous Operation' },
                        { code: 'IDV.21', description: 'Aeronautical Software Engineering' },
                        { code: 'IDV.22', description: 'Development of Operational Research Tools' },
                        { code: 'IDV.23', description: 'Synthetic Environment & Virtual Reality Tools' },
                        { code: 'IDV.24', description: 'Aircraft Performance Assessment' },
                        { code: 'IDV.25', description: 'Airport Performance Assessment' },
                        { code: 'IDV.26', description: 'Business Modelling' },
                        { code: 'IDV.27', description: 'Numerical Models (including Fast Time Simulation)' },
                        { code: 'IDV.28', description: 'Real Time Simulators' },
                        { code: 'IDV.29', description: 'General Purpose Equipment' },
                        { code: 'IDV.30', description: 'Reference Data for R&D and Live/RT Use' },
                        { code: 'IDV.31', description: 'Methodology' },
                        { code: 'IDV.32', description: 'Large Scale Validation Experiments/Platforms' },
                        { code: 'IDV.33', description: 'EcoDesign & Engineering for Sustainability' }
                    ]
                },
                'AOP': {
                    name: 'Aircraft Operations (AOP)',
                    subdomains: [
                        { code: 'AOP.1', description: 'Air Traffic Management' },
                        { code: 'AOP.2', description: 'Airports' },
                        { code: 'AOP.3', description: 'Maintenance, Repair & Overhaul (MRO)' }
                    ]
                },
                'UAS': {
                    name: 'Unmanned Aerial Systems (UAS)',
                    subdomains: [
                        { code: 'UAS.1', description: 'UAS & Scaled Flight Testing' }
                    ]
                },
                'HFA': {
                    name: 'Human Factors (HFA)',
                    subdomains: [
                        { code: 'HFA.1', description: 'Human Factors Integration & Man-Machine Interface' },
                        { code: 'HFA.2', description: 'Human Information Processing' },
                        { code: 'HFA.3', description: 'Human Performance Modelling & Enhancement' },
                        { code: 'HFA.4', description: 'Selection & Training' },
                        { code: 'HFA.5', description: 'Human Survivability, Protection & Stress Effects' },
                        { code: 'HFA.6', description: 'Human Element in Security' }
                    ]
                },
                'ICS': {
                    name: 'Innovative Concepts & Scenarios (ICS)',
                    subdomains: [
                        { code: 'ICS.1', description: 'Scenarios Analysis' },
                        { code: 'ICS.2', description: 'Unconventional Configurations & New Aircraft Concepts' },
                        { code: 'ICS.3', description: 'Breakthrough Technologies' },
                        { code: 'ICS.4', description: 'Industry 4.0 to Industry 5.0' }
                    ]
                }
            },
            NASA: {
                'PS': {
                    name: 'Propulsion Systems',
                    subdomains: [
                        { code: 'PS.1', description: 'Chemical Propulsion' },
                        { code: 'PS.2', description: 'Electric Space Propulsion' },
                        { code: 'PS.3', description: 'Aero Propulsion' },
                        { code: 'PS.4', description: 'Advanced Propulsion' }
                    ]
                },
                'FCA': {
                    name: 'Flight Computing & Avionics',
                    subdomains: [
                        { code: 'FCA.1', description: 'Avionics Component Technologies' },
                        { code: 'FCA.2', description: 'Avionics Systems & Subsystems' },
                        { code: 'FCA.3', description: 'Avionics Tools, Models & Analysis' }
                    ]
                },
                'APES': {
                    name: 'Aerospace Power & Energy Storage',
                    subdomains: [
                        { code: 'APES.1', description: 'Power Generation & Energy Conversion' },
                        { code: 'APES.2', description: 'Energy Storage' },
                        { code: 'APES.3', description: 'Power Management & Distribution' }
                    ]
                },
                'RS': {
                    name: 'Robotic Systems',
                    subdomains: [
                        { code: 'RS.1', description: 'Sensing & Perception' },
                        { code: 'RS.2', description: 'Mobility' },
                        { code: 'RS.3', description: 'Manipulation' },
                        { code: 'RS.4', description: 'Human-Robot Interaction' },
                        { code: 'RS.5', description: 'Autonomous Rendezvous & Docking' },
                        { code: 'RS.6', description: 'Robotics Integration' }
                    ]
                },
                'CNDT': {
                    name: 'Communications, Navigation & Debris Tracking',
                    subdomains: [
                        { code: 'CNDT.1', description: 'Optical Communications' },
                        { code: 'CNDT.2', description: 'Radio Frequency' },
                        { code: 'CNDT.3', description: 'Internetworking' },
                        { code: 'CNDT.4', description: 'Network Provided Position/Navigation/Timing' },
                        { code: 'CNDT.5', description: 'Revolutionary Communications Technologies' },
                        { code: 'CNDT.6', description: 'Ground-Based Debris Tracking & Management' },
                        { code: 'CNDT.7', description: 'Acoustic Communications' }
                    ]
                },
                'HLSH': {
                    name: 'Human Health, Life Support & Habitation',
                    subdomains: [
                        { code: 'HLSH.1', description: 'Environmental Control & Life Support' },
                        { code: 'HLSH.2', description: 'Extravehicular Activity Systems' },
                        { code: 'HLSH.3', description: 'Human Health & Performance' },
                        { code: 'HLSH.4', description: 'Environmental Monitoring & Safety' },
                        { code: 'HLSH.5', description: 'Radiation' },
                        { code: 'HLSH.6', description: 'Human Systems Integration' }
                    ]
                },
                'EDS': {
                    name: 'Exploration Destination Systems',
                    subdomains: [
                        { code: 'EDS.1', description: 'In-Situ Resource Utilization' },
                        { code: 'EDS.2', description: 'Mission Infrastructure, Sustainability & Support' },
                        { code: 'EDS.3', description: 'Mission Operations & Safety' }
                    ]
                },
                'SI': {
                    name: 'Sensors & Instruments',
                    subdomains: [
                        { code: 'SI.1', description: 'Remote Sensing Instruments' },
                        { code: 'SI.2', description: 'Observatories' },
                        { code: 'SI.3', description: 'In-Situ Instruments' }
                    ]
                },
                'EDL': {
                    name: 'Entry, Descent & Landing',
                    subdomains: [
                        { code: 'EDL.1', description: 'Aeroassist & Atmospheric Entry' },
                        { code: 'EDL.2', description: 'Descent' },
                        { code: 'EDL.3', description: 'Landing' },
                        { code: 'EDL.4', description: 'Vehicle Systems' }
                    ]
                },
                'AS': {
                    name: 'Autonomous Systems',
                    subdomains: [
                        { code: 'AS.1', description: 'Situational & Self Awareness' },
                        { code: 'AS.2', description: 'Reasoning & Acting' },
                        { code: 'AS.3', description: 'Collaboration & Interaction' },
                        { code: 'AS.4', description: 'Engineering & Integrity' }
                    ]
                },
                'SMSIP': {
                    name: 'Software, Modeling, Simulation & Information Processing',
                    subdomains: [
                        { code: 'SMSIP.1', description: 'Software Development & Integrity' },
                        { code: 'SMSIP.2', description: 'Modeling' },
                        { code: 'SMSIP.3', description: 'Simulation' },
                        { code: 'SMSIP.4', description: 'Information Processing' },
                        { code: 'SMSIP.5', description: 'Mission Architecture & Concept Development' },
                        { code: 'SMSIP.6', description: 'Ground Computing' }
                    ]
                },
                'MSMM': {
                    name: 'Materials, Structures, Mechanical Systems & Manufacturing',
                    subdomains: [
                        { code: 'MSMM.1', description: 'Materials' },
                        { code: 'MSMM.2', description: 'Structures' },
                        { code: 'MSMM.3', description: 'Mechanical Systems' },
                        { code: 'MSMM.4', description: 'Manufacturing' },
                        { code: 'MSMM.5', description: 'Structural Dynamics' }
                    ]
                },
                'GTSS': {
                    name: 'Ground, Test & Surface Systems',
                    subdomains: [
                        { code: 'GTSS.1', description: 'Infrastructure Optimization' },
                        { code: 'GTSS.2', description: 'Test & Qualification' },
                        { code: 'GTSS.3', description: 'Assembly, Integration & Launch' },
                        { code: 'GTSS.4', description: 'Mission Success Technologies' }
                    ]
                },
                'TMS': {
                    name: 'Thermal Management Systems',
                    subdomains: [
                        { code: 'TMS.1', description: 'Cryogenic Systems' },
                        { code: 'TMS.2', description: 'Thermal Control Components' },
                        { code: 'TMS.3', description: 'Thermal Protection Components' }
                    ]
                },
                'FVS': {
                    name: 'Flight Vehicle Systems',
                    subdomains: [
                        { code: 'FVS.1', description: 'Aerosciences' },
                        { code: 'FVS.2', description: 'Flight Mechanics' }
                    ]
                },
                'ATMRT': {
                    name: 'Air Traffic Management & Range Tracking',
                    subdomains: [
                        { code: 'ATMRT.1', description: 'Safe All Vehicle Access' },
                        { code: 'ATMRT.2', description: 'Weather/Environment' },
                        { code: 'ATMRT.3', description: 'Traffic Management Concepts' },
                        { code: 'ATMRT.4', description: 'Architectures & Infrastructure' },
                        { code: 'ATMRT.5', description: 'Tracking, Surveillance & Flight Safety' },
                        { code: 'ATMRT.6', description: 'Integrated Modeling, Simulation & Testing' }
                    ]
                },
                'GNC': {
                    name: 'Guidance, Navigation & Control (GN&C)',
                    subdomains: [
                        { code: 'GNC.1', description: 'Guidance & Targeting Algorithms' },
                        { code: 'GNC.2', description: 'Navigation Technologies' },
                        { code: 'GNC.3', description: 'Control Technologies' },
                        { code: 'GNC.4', description: 'Attitude Estimation Technologies' },
                        { code: 'GNC.5', description: 'GN&C Systems Engineering' },
                        { code: 'GNC.6', description: 'Trajectory Generation & Optimization for Airspace' }
                    ]
                }
            }
        };
        
        // Função para alternar a visibilidade dos subdomínios
        function toggleSubdomains(containerId) {
            var container = document.getElementById(containerId);
            if (container.style.display === 'flex') {
                container.style.display = 'none';
            } else {
                container.style.display = 'flex';
            }
        }
        
        // Variáveis de estado
        var currentRowIndex = null;
        var currentProgram = 'ACARE';
        var selectedSubdomain = null;
        var approvals = {};
        var feedbacks = {};
        var manualSubdomains = {};
        
        // Inicializa os gráficos
        function initializeCharts() {
            <?php foreach ($processedData as $index => $row): ?>
                // Gráfico ACARE
                var acareCtx = document.getElementById('acareChart-<?php echo $index; ?>').getContext('2d');
                var acareChart = new Chart(acareCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_map(function($c) { return $c['code']; }, $row['ACARE'])); ?>,
                        datasets: [{
                            label: 'Pontuação ACARE',
                            data: <?php echo json_encode(array_map(function($c) { return $c['score']; }, $row['ACARE'])); ?>,
                            backgroundColor: [
                                'rgba(75, 192, 192, 0.6)',
                                'rgba(54, 162, 235, 0.6)',
                                'rgba(153, 102, 255, 0.6)'
                            ],
                            borderColor: [
                                'rgba(75, 192, 192, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(153, 102, 255, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 1.0,
                                ticks: {
                                    color: '#e0e0e0'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#e0e0e0'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        var label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.parsed.y.toFixed(3);
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Gráfico NASA
                var nasaCtx = document.getElementById('nasaChart-<?php echo $index; ?>').getContext('2d');
                var nasaChart = new Chart(nasaCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_map(function($c) { return $c['code']; }, $row['NASA'])); ?>,
                        datasets: [{
                            label: 'Pontuação NASA',
                            data: <?php echo json_encode(array_map(function($c) { return $c['score']; }, $row['NASA'])); ?>,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.6)',
                                'rgba(255, 159, 64, 0.6)',
                                'rgba(255, 205, 86, 0.6)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(255, 159, 64, 1)',
                                'rgba(255, 205, 86, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 1.0,
                                ticks: {
                                    color: '#e0e0e0'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#e0e0e0'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        var label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.parsed.y.toFixed(3);
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            <?php endforeach; ?>
        }
        
        $(document).ready(function() {
            initializeCharts();
            
            // Filtro por programa
            $('#programFilter').change(function() {
                var program = $(this).val();
                $('tbody tr').each(function() {
                    var rowProgram = $(this).data('program');
                    if (!program || rowProgram === program) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // Botões de aprovação/rejeição
            $('.btn-approve').click(function() {
                var userName = $('#userName').val();
                if (!userName) {
                    alert('Por favor, digite seu nome antes de aprovar.');
                    return;
                }
                
                var classificationDiv = $(this).closest('.classification');
                var rowIndex = classificationDiv.closest('tr').data('row');
                var type = classificationDiv.data('type');
                var index = classificationDiv.data('index');
                
                // Marca como aprovado
                classificationDiv.addClass('approved').removeClass('rejected');
                approvals[rowIndex + '-' + type + '-' + index] = 'Approved';
                
                // Atualiza o gráfico
                updateChartColors(rowIndex, type);
            });
            
            $('.btn-reject').click(function() {
                var userName = $('#userName').val();
                if (!userName) {
                    alert('Por favor, digite seu nome antes de rejeitar.');
                    return;
                }
                
                var classificationDiv = $(this).closest('.classification');
                var rowIndex = classificationDiv.closest('tr').data('row');
                var type = classificationDiv.data('type');
                var index = classificationDiv.data('index');
                
                // Marca como rejeitado
                classificationDiv.addClass('rejected').removeClass('approved');
                approvals[rowIndex + '-' + type + '-' + index] = 'Rejected';
                
                // Atualiza o gráfico
                updateChartColors(rowIndex, type);
            });
            
            // Função para atualizar cores dos gráficos
            function updateChartColors(rowIndex, type) {
                var chartId = type + 'Chart-' + rowIndex;
                var chart = Chart.getChart(chartId);
                if (chart) {
                    chart.data.datasets[0].backgroundColor = chart.data.labels.map(function(label, i) {
                        var key = rowIndex + '-' + type + '-' + i;
                        if (approvals[key] === 'Approved') {
                            return 'rgba(0, 200, 0, 0.6)'; // Verde para aprovado
                        } else if (approvals[key] === 'Rejected') {
                            return 'rgba(200, 0, 0, 0.6)'; // Vermelho para rejeitado
                        } else {
                            // Cores padrão baseadas no tipo de gráfico
                            if (type === 'acare') {
                                return ['rgba(75, 192, 192, 0.6)', 'rgba(54, 162, 235, 0.6)', 'rgba(153, 102, 255, 0.6)'][i % 3];
                            } else {
                                return ['rgba(255, 99, 132, 0.6)', 'rgba(255, 159, 64, 0.6)', 'rgba(255, 205, 86, 0.6)'][i % 3];
                            }
                        }
                    });
                    chart.update();
                }
            }
            
            // Botão de feedback
            $('.btn-feedback').click(function() {
                currentRowIndex = $(this).closest('tr').data('row');
                $('#modalRowNumber').text(currentRowIndex + 1);
                
                // Carrega feedbacks existentes
                var rowFeedbacks = feedbacks[currentRowIndex] || [];
                $('#feedbackList').empty();
                
                if (rowFeedbacks.length > 0) {
                    $.each(rowFeedbacks, function(i, feedback) {
                        $('#feedbackList').append($('<p>').text(feedback).addClass('border-bottom').css('padding-bottom', '5px'));
                    });
                } else {
                    $('#feedbackList').append($('<p>').text('Sem feedback ainda.').css('color', '#777'));
                }
                
                $('#newFeedback').val('');
                $('#feedbackModal').modal('show');
            });
            
            // Enviar feedback
            $('#submitFeedback').click(function() {
                var feedback = $('#newFeedback').val().trim();
                if (feedback) {
                    if (!feedbacks[currentRowIndex]) {
                        feedbacks[currentRowIndex] = [];
                    }
                    feedbacks[currentRowIndex].push(feedback);
                    $('#feedbackModal').modal('hide');
                }
            });
            
            // Botão para adicionar subdomínio
            $('.btn-add-subdomain').click(function() {
                currentRowIndex = $(this).closest('tr').data('row');
                selectedSubdomain = null;
                
                // Preenche os domínios ACARE
                $('#acareDomains').empty();
                $.each(subdomains.ACARE, function(domainCode, domainData) {
                    var domainHeader = $('<h4 class="domain-toggle">')
                        .text(domainCode + ' - ' + domainData.name)
                        .click(function() {
                            $(this).next('.subdomain-list').toggle();
                        });
                    
                    var subdomainList = $('<div class="subdomain-list" style="display:none; padding-left:20px;">');
                    
                    $.each(domainData.subdomains, function(i, subdomain) {
                        subdomainList.append(
                            $('<div class="subdomain-item">').html(
                                '<input type="radio" name="acareSubdomain" value="' + subdomain.code + '" id="acare-' + subdomain.code + '"> ' +
                                '<label for="acare-' + subdomain.code + '">' + subdomain.code + ' - ' + subdomain.description + '</label>'
                            ).click(function() {
                                selectedSubdomain = 'ACARE:' + subdomain.code;
                                $('input[name="acareSubdomain"]').prop('checked', false);
                                $(this).find('input').prop('checked', true);
                            })
                        );
                    });
                    
                    $('#acareDomains').append(domainHeader).append(subdomainList);
                });
                
                // Preenche os domínios NASA
                $('#nasaDomains').empty();
                $.each(subdomains.NASA, function(domainCode, domainData) {
                    var domainHeader = $('<h4 class="domain-toggle">')
                        .text(domainCode + ' - ' + domainData.name)
                        .click(function() {
                            $(this).next('.subdomain-list').toggle();
                        });
                    
                    var subdomainList = $('<div class="subdomain-list" style="display:none; padding-left:20px;">');
                    
                    $.each(domainData.subdomains, function(i, subdomain) {
                        subdomainList.append(
                            $('<div class="subdomain-item">').html(
                                '<input type="radio" name="nasaSubdomain" value="' + subdomain.code + '" id="nasa-' + subdomain.code + '"> ' +
                                '<label for="nasa-' + subdomain.code + '">' + subdomain.code + ' - ' + subdomain.description + '</label>'
                            ).click(function() {
                                selectedSubdomain = 'NASA:' + subdomain.code;
                                $('input[name="nasaSubdomain"]').prop('checked', false);
                                $(this).find('input').prop('checked', true);
                            })
                        );
                    });
                    
                    $('#nasaDomains').append(domainHeader).append(subdomainList);
                });
                
                $('#subdomainModal').modal('show');
            });
                        // Botão para limpar seleções
            $('.btn-clear-selections').click(function() {
                var row = $(this).closest('tr');
                var rowIndex = row.data('row');
                
                // Remove todas as aprovações/rejeições desta linha
                for (var key in approvals) {
                    if (key.startsWith(rowIndex + '-')) {
                        delete approvals[key];
                    }
                }
                
                // Remove subdomínios manuais para esta linha
                if (manualSubdomains[rowIndex]) {
                    delete manualSubdomains[rowIndex];
                }
                
                // Remove feedbacks desta linha
                if (feedbacks[rowIndex]) {
                    delete feedbacks[rowIndex];
                }
                
                // Remove classes de aprovação/rejeição
                row.find('.classification').removeClass('approved rejected');
                
                // Atualiza gráficos para restaurar cores originais
                updateChartColors(rowIndex, 'acare');
                updateChartColors(rowIndex, 'nasa');
                
                // Remove qualquer mensagem de alerta anterior
                alert('Seleções limpas com sucesso.');
            });
            // Criar subdomínio personalizado ACARE
            $('#addCustomAcare').click(function() {
                var code = prompt('Digite o subdomínio personalizado:');
                if (code) {
                    var description = prompt('Digite a descrição:');
                    if (description) {
                        selectedSubdomain = 'ACARE:custom:' + code + '.' + description;
                    }
                }
            });
            
            // Criar subdomínio personalizado NASA
            $('#addCustomNasa').click(function() {
                var code = prompt('Digite o subdomínio personalizado:');
                if (code) {
                    var description = prompt('Digite a descrição:');
                    if (description) {
                        selectedSubdomain = 'NASA:custom:' + code + '.' + description;
                    }
                }
            });
            
            // Adicionar subdomínio selecionado
            // Adicionar subdomínio selecionado
            $('#submitSubdomain').click(function() {
                if (!selectedSubdomain || currentRowIndex === null) {
                    alert('Por favor, selecione um subdomínio.');
                    return;
                }
                
                var parts = selectedSubdomain.split(':');
                var program = parts[0];
                var type = parts[1];
                var code, description;
                
                if (type === 'custom') {
                    var customParts = parts[2].split('.');
                    code = 'custom_' + customParts[0];
                    description = customParts.slice(1).join('.');
                } else {
                    // Busca a descrição do domínio ou subdomínio
                    var found = false;
                    $.each(subdomains[program], function(domainCode, domainData) {
                        if (domainCode === type) {
                            code = domainCode;
                            description = domainData.name;
                            found = true;
                            return false;
                        }
                        
                        $.each(domainData.subdomains, function(i, subdomain) {
                            if (subdomain.code === type) {
                                code = domainCode;
                                description = domainData.name;
                                found = true;
                                return false;
                            }
                        });
                        if (found) return false;
                    });
                    
                    if (!found) {
                        code = 'custom_' + type;
                        description = 'Domínio personalizado';
                    }
                }
                
                // Armazena o domínio manual
                if (!manualSubdomains[currentRowIndex]) {
                    manualSubdomains[currentRowIndex] = {
                        ACARE: [],
                        NASA: [],
                        customACARE: [],
                        customNASA: []
                    };
                }

                // Determina o índice para esta classificação manual
                var manualIndex = 0;
                if (type === 'custom') {
                    manualIndex = manualSubdomains[currentRowIndex].customACARE.length;
                    manualSubdomains[currentRowIndex].customACARE.push({
                        code: code,
                        description: description,
                        score: 0.0,
                        isDomain: true
                    });
                } else {
                    manualIndex = manualSubdomains[currentRowIndex][program].length;
                    manualSubdomains[currentRowIndex][program].push({
                        code: code,
                        description: description,
                        score: 0.0,
                        isDomain: true
                    });
                }

                // Registra automaticamente como aprovado
                var approvalKey = currentRowIndex + '-' + program.toLowerCase() + '-custom-' + manualIndex;
                approvals[approvalKey] = 'Approved';

                $('#subdomainModal').modal('hide');
                alert('Domínio adicionado e automaticamente aprovado. Ele será incluído no Excel.');
            });
            // Botão para baixar Excel
            $('#downloadExcelBtn').click(function() {
                var userName = $('#userName').val().trim();
                if (!userName) {
                    alert('Por favor, digite seu nome antes de baixar o Excel.');
                    return;
                }
                
                var cleanedUserName = userName.replace(/[^a-zA-Z0-9]/g, '_') || 'classification';
                
                try {
                    // Create workbook
                    var wb = XLSX.utils.book_new();
                    
                    // Create approvals worksheet with new columns
                    var approvalData = [
                        ["Programa", "Área", "Linha de Pesquisa", "Usuário", "Domínio", "Subdomínio", "Classificação", "Status", "Score", "Feedback", "Data"]
                    ];
                    
                    // Process table rows
                    $('tbody tr').each(function() {
                        var rowIndex = $(this).data('row');
                        var rowData = <?php echo json_encode($processedData); ?>[rowIndex];
                        var rowFeedbacks = feedbacks[rowIndex] || [];
                        
                        // Helper function to add classification data with domain
                        // Helper function to add classification data with domain
                        // Helper function to add classification data with domain
                        function addClassificationData(type, index, classification, status) {
                            var domain, subdomain;
                            
                            if (classification.isDomain) {
                                domain = classification.code.replace('custom_', '') + ' - ' + classification.description;
                                subdomain = 'N/A';
                            } else {
                                domain = findParentDomain(type.toUpperCase(), classification.code.replace('custom_', ''));
                                subdomain = classification.code.replace('custom_', '') + ' - ' + classification.description;
                            }
                            
                            var prefix = classification.code.startsWith('custom_') ? 
                                        type + ' Personalizado' : 
                                        type + ' ' + (index + 1);
                            
                            // SEMPRE marca como Aprovado se for uma classificação manual
                            var finalStatus = (classification.code.startsWith('custom_') || status === 'Aprovado') ? 'Aprovado' : status;
                            
                            approvalData.push([
                                rowData.Programa,
                                rowData.Área,
                                rowData.Linha_de_Pesquisa,
                                userName,
                                domain,
                                subdomain,
                                prefix + ': ' + classification.code.replace('custom_', '') + ' - ' + classification.description,
                                finalStatus,
                                classification.score.toFixed(3),
                                rowFeedbacks.join('; '),
                                new Date().toLocaleString()
                            ]);
                        }                        
                        // Process ACARE classifications (3 originais)
                        for (var i = 0; i < 3; i++) {
                            var acareKey = rowIndex + '-acare-' + i;
                            var acareClass = rowData.ACARE[i] || {code: 'N/A', description: 'Sem classificação', score: 0};
                            var acareStatus = approvals[acareKey] === 'Approved' ? 'Aprovado' : 
                                            approvals[acareKey] === 'Rejected' ? 'Rejeitado' : 'Não avaliado';
                            addClassificationData('ACARE', i, acareClass, acareStatus);
                        }
                        
                        // Process NASA classifications (3 originais)
                        for (var i = 0; i < 3; i++) {
                            var nasaKey = rowIndex + '-nasa-' + i;
                            var nasaClass = rowData.NASA[i] || {code: 'N/A', description: 'Sem classificação', score: 0};
                            var nasaStatus = approvals[nasaKey] === 'Approved' ? 'Aprovado' : 
                                            approvals[nasaKey] === 'Rejected' ? 'Rejeitado' : 'Não avaliado';
                            addClassificationData('NASA', i, nasaClass, nasaStatus);
                        }
                        
                        // Process manual subdomains (additional rows)
                        // Process manual subdomains (additional rows)
                        if (manualSubdomains[rowIndex]) {
                            // ACARE custom subdomains
                            if (manualSubdomains[rowIndex]['ACARE']) {
                                manualSubdomains[rowIndex]['ACARE'].forEach(function(sub, idx) {
                                    addClassificationData('ACARE', idx, sub, 'Aprovado'); // Força status como Aprovado
                                });
                            }
                            
                            // NASA custom subdomains
                            if (manualSubdomains[rowIndex]['NASA']) {
                                manualSubdomains[rowIndex]['NASA'].forEach(function(sub, idx) {
                                    addClassificationData('NASA', idx, sub, 'Aprovado'); // Força status como Aprovado
                                });
                            }
                            
                            // ACARE custom (personalizados)
                            if (manualSubdomains[rowIndex]['customACARE']) {
                                manualSubdomains[rowIndex]['customACARE'].forEach(function(sub, idx) {
                                    addClassificationData('ACARE', idx, sub, 'Aprovado'); // Força status como Aprovado
                                });
                            }
                            
                            // NASA custom (personalizados)
                            if (manualSubdomains[rowIndex]['customNASA']) {
                                manualSubdomains[rowIndex]['customNASA'].forEach(function(sub, idx) {
                                    addClassificationData('NASA', idx, sub, 'Aprovado'); // Força status como Aprovado
                                });
                            }
                        }
                    });
                    
                    // Create worksheet with formatting
                    var ws = XLSX.utils.aoa_to_sheet(approvalData);
                    var range = XLSX.utils.decode_range(ws['!ref']);
                    
                    // Set column widths
                    ws['!cols'] = [
                        { wch: 20 }, // Programa
                        { wch: 20 }, // Área
                        { wch: 30 }, // Linha de Pesquisa
                        { wch: 15 }, // Usuário
                        { wch: 25 }, // Domínio
                        { wch: 10 }, // Classificação
                        { wch: 10 }, // Status
                        { wch: 10 }, // Score
                        { wch: 30 }, // Feedback
                        { wch: 20 }  // Data
                    ];
                    
                    // Format header row
                    for (var C = range.s.c; C <= range.e.c; ++C) {
                        var cell_ref = XLSX.utils.encode_cell({r:0, c:C});
                        ws[cell_ref].s = {
                            font: { bold: true, color: { rgb: "FFFFFF" } },
                            fill: { fgColor: { rgb: "1A3B6A" }, patternType: "solid" },
                            alignment: { horizontal: "center", vertical: "center", wrapText: true },
                            border: {
                                top:    { style: "medium", color: { rgb: "000000" } },
                                bottom: { style: "medium", color: { rgb: "000000" } },
                                left:   { style: "medium", color: { rgb: "000000" } },
                                right:  { style: "medium", color: { rgb: "000000" } }
                            }
                        };
                    }
                    
                    // Format data rows with alternating colors
                    for (var R = 1; R <= range.e.r; ++R) {
                        for (var C = 0; C <= range.e.c; ++C) {
                            var cell_ref = XLSX.utils.encode_cell({r:R, c:C});
                            if (!ws[cell_ref]) {
                                ws[cell_ref] = { t:'s', v: approvalData[R][C] };
                            }
                            ws[cell_ref].s = {
                                fill: { fgColor: { rgb: R % 2 === 0 ? "F5F6F5" : "FFFFFF" }, patternType: "solid" },
                                border: {
                                    top:    { style: "thin", color: { rgb: "000000" } },
                                    bottom: { style: "thin", color: { rgb: "000000" } },
                                    left:   { style: "thin", color: { rgb: "000000" } },
                                    right:  { style: "thin", color: { rgb: "000000" } }
                                }
                            };
                        }
                    }
                    
                    XLSX.utils.book_append_sheet(wb, ws, "Aprovações");
                    
                    // Create summary worksheet (unchanged)
                    var summaryData = [
                        ["Resumo de Aprovações"],
                        ["Usuário:", userName],
                        ["Data:", new Date().toLocaleString()],
                        ["", ""],
                        ["Total de Aprovações:", approvalData.length - 1]
                    ];
                    
                    var programStats = {};
                    for (var i = 1; i < approvalData.length; i++) {
                        var program = approvalData[i][0];
                        programStats[program] = (programStats[program] || 0) + 1;
                    }
                    
                    summaryData.push(["", ""]);
                    summaryData.push(["Aprovações por Programa:", ""]);
                    for (var program in programStats) {
                        summaryData.push([program, programStats[program]]);
                    }
                    
                    var wsSummary = XLSX.utils.aoa_to_sheet(summaryData);
                    wsSummary['!cols'] = [{ wch: 30 }, { wch: 20 }];
                    XLSX.utils.book_append_sheet(wb, wsSummary, "Resumo");
                    
                    // Generate Excel file
                    var fileName = cleanedUserName + '_aprovacoes_' + new Date().toISOString().slice(0, 10) + '.xlsx';
                    XLSX.writeFile(wb, fileName);
                    
                } catch (error) {
                    console.error("Erro ao gerar Excel:", error);
                    alert("Ocorreu um erro ao gerar o arquivo Excel. Por favor, tente novamente.");
                }
            });

// Helper function to find parent domain
// Helper function to find parent domain
        function findParentDomain(program, subdomainCode) {
            var hierarchy = <?php echo json_encode([
                'ACARE' => $acareHierarchy, 
                'NASA' => $nasaHierarchy
            ]); ?>;
            
            if (!subdomainCode || subdomainCode === 'N/A') return 'N/A';
            
            // Remove any custom prefix
            subdomainCode = subdomainCode.replace('custom_', '');
            
            // Search through the hierarchy
            for (var domain in hierarchy[program]) {
                // Check if it's a domain (no subdomains)
                if (domain === subdomainCode) {
                    return domain.split('.').slice(1).join('.').trim();
                }
                
                // Check subdomains
                for (var code in hierarchy[program][domain]) {
                    if (code === subdomainCode) {
                        return domain.split('.').slice(1).join('.').trim();
                    }
                }
            }
            return 'N/A';
        }    
            // Botão para baixar CSV
            $('#downloadBtn').click(function() {
                var userName = $('#userName').val().trim();
                if (!userName) {
                    alert('Por favor, digite seu nome antes de baixar o CSV.');
                    return;
                }
                
                // Cria um formulário temporário
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                // Adiciona os campos necessários
                function addField(name, value) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                }
                
                addField('download_consolidated', 'true');
                addField('user_name', userName);
                addField('approvals', JSON.stringify(approvals));
                addField('feedbacks', JSON.stringify(feedbacks));
                addField('manual_subdomains', JSON.stringify(manualSubdomains));
                
                // Adiciona o formulário ao body e submete
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
        });  
        // Função auxiliar para criar div de classificação
        function createClassificationDiv(classification, type, index) {
            // Se for um subdomínio personalizado, exibe "N/A" no site
            if (classification.code.startsWith('custom_') || classification.code === 'N/A') {
                return $('<div>').addClass('classification').text('N/A: Sem classificação')
                    .data('type', type).data('index', index);
            }
            
            // Se não for personalizado, exibe normalmente
            var div = $('<div>').addClass('classification').data('type', type).data('index', index);
            var text = classification.code + ': ' + classification.description;
            div.text(text);
            
            // Adiciona botões de aprovação/rejeição
            div.append(
                $('<button>').addClass('btn btn-sm btn-success btn-approve').text('Aprovar'),
                $('<button>').addClass('btn btn-sm btn-danger btn-reject').text('Rejeitar')
            );
            
            return div;
        }
    </script>
</body>
</html>
