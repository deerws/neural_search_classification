<?php
// Configurações iniciais
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

// Função para ler o CSV
function readCSV($filename) {
    $data = array();
    
    if (!file_exists($filename) || !is_readable($filename)) {
        trigger_error("Arquivo CSV não encontrado ou não pode ser lido: " . $filename, E_USER_WARNING);
        return $data;
    }
    
    $content = file_get_contents($filename);
    $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    
    if ($encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding ?: 'auto');
    }
    
    $bom = pack('H*','EFBBBF');
    $content = preg_replace("/^$bom/", '', $content);
    
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($tempFile, $content);
    
    if (($handle = fopen($tempFile, "r")) !== FALSE) {
        $header = fgetcsv($handle, 0, ";");
        if ($header === false) {
            fclose($handle);
            unlink($tempFile);
            return $data;
        }
        
        $header = array_map('trim', $header);
        
        while (($row = fgetcsv($handle, 0, ";")) !== FALSE) {
            if (count($header) == count($row)) {
                $data[] = array_combine($header, $row);
            }
        }
        
        fclose($handle);
    }
    
    unlink($tempFile);
    return $data;
}

// Funções auxiliares para filtros
function filterByTerm($data, $term) {
    if (empty($term)) return $data;
    
    $filtered = array();
    foreach ($data as $row) {
        foreach ($row as $value) {
            if (stripos($value, $term) !== FALSE) {
                $filtered[] = $row;
                break;
            }
        }
    }
    return $filtered;
}

function filterByField($data, $field, $value) {
    if (empty($value)) return $data;
    
    $filtered = array();
    foreach ($data as $row) {
        if (isset($row[$field]) && $row[$field] == $value) {
            $filtered[] = $row;
        }
    }
    return $filtered;
}

// Carregar dados
$csvFile = 'final_aprovações_BACKUP_1_updated.csv';
$data = file_exists($csvFile) ? readCSV($csvFile) : array();

// Aplicar filtros
$filters = array(
    'search' => isset($_GET['search']) ? $_GET['search'] : '',
    'programa' => isset($_GET['programa']) ? $_GET['programa'] : '',
    'area' => isset($_GET['area']) ? $_GET['area'] : '',
    'status' => isset($_GET['status']) ? $_GET['status'] : '',
    'professor' => isset($_GET['professor']) ? $_GET['professor'] : '',
    'segmento' => isset($_GET['segmento']) ? $_GET['segmento'] : ''
);

$filteredData = $data;
foreach ($filters as $field => $value) {
    if (!empty($value)) {
        if ($field == 'search') {
            $filteredData = filterByTerm($filteredData, $value);
        } else {
            $fieldName = ($field == 'professor') ? 'Usuário' : ($field == 'segmento' ? 'Segmento' : ucfirst($field));
            $filteredData = filterByField($filteredData, $fieldName, $value);
        }
    }
}

// Remover registros "Em Análise" da tabela
$filteredData = array_filter($filteredData, function($row) {
    return isset($row['Status']) && $row['Status'] !== 'Em Análise';
});

// Preparar dados para visualização
$stats = array(
    'total' => count($data),
    'aprovados' => 0,
    'rejeitados' => 0,
    'em_analise' => 0
);

foreach ($data as $row) {
    if (isset($row['Status'])) {
        switch (strtolower(trim($row['Status']))) {
            case 'aprovado': $stats['aprovados']++; break;
            case 'rejeitado': $stats['rejeitados']++; break;
            case 'em análise': $stats['em_analise']++; break;
        }
    }
}

// Preparar dados para o gráfico de corda (Linha de Pesquisa x Área)
$researchRelations = array();
foreach ($filteredData as $row) {
    if (!isset($row['Linha de Pesquisa']) || !isset($row['Área'])) continue;
    
    $linha = $row['Linha de Pesquisa'];
    $area = $row['Área'];
    
    if (!isset($researchRelations[$linha])) {
        $researchRelations[$linha] = array();
    }
    $researchRelations[$linha][$area] = ($researchRelations[$linha][$area] ?? 0) + 1;
}

// Converter para formato do chord diagram
$allItems = array_values(array_unique(array_merge(
    array_keys($researchRelations),
    array_keys(array_merge(...array_values($researchRelations)))
)));

$matrix = array_fill(0, count($allItems), array_fill(0, count($allItems), 0));

foreach ($researchRelations as $linha => $relations) {
    $i = array_search($linha, $allItems);
    foreach ($relations as $area => $count) {
        $j = array_search($area, $allItems);
        $matrix[$i][$j] += $count;
        $matrix[$j][$i] += $count;
    }
}

// Obter valores únicos para filtros
$programas = array();
$areasFiltro = array();
$statusList = array();
$professores = array();
$segmentos = array();

foreach ($data as $row) {
    if (isset($row['Programa']) && !in_array($row['Programa'], $programas) && !empty($row['Programa'])) {
        $programas[] = $row['Programa'];
    }
    if (isset($row['Área']) && !in_array($row['Área'], $areasFiltro) && !empty($row['Área'])) {
        $areasFiltro[] = $row['Área'];
    }
    if (isset($row['Status']) && !in_array($row['Status'], $statusList) && !empty($row['Status'])) {
        $statusList[] = $row['Status'];
    }
    if (isset($row['Usuário']) && !in_array($row['Usuário'], $professores) && !empty($row['Usuário'])) {
        $professores[] = $row['Usuário'];
    }
    if (isset($row['Segmento']) && !in_array($row['Segmento'], $segmentos) && !empty($row['Segmento'])) {
        $segmentos[] = $row['Segmento'];
    }
}

sort($programas);
sort($areasFiltro);
sort($statusList);
sort($professores);
sort($segmentos);

// Função para formatar Subdomínio
function formatSubdomain($subdomain, $classification) {
    $value = trim($classification);
    // Remove prefixos "NASA X", "ACARE X", ou "ACARE Manual X"
    $value = preg_replace('/^(ACARE|NASA)\s*(Manual\s*)?\d+:/i', '', $value);
    // Adiciona o código do subdomínio no início
    $code = trim($subdomain);
    return $code . ': ' . trim($value);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxonomia de Pesquisa - Consulta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            padding: 20px;
        }
        
        .container-fluid {
            width: 100%;
            max-width: none;
            padding: 0 15px;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .logo-title-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-logo {
            width: 200px;
            height: 200px;
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
        
        .participate-button {
            background-color: #1976d2;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .participate-button:hover {
            opacity: 0.85;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            text-decoration: none;
            color: white;
        }
        
        .chord-container {
            width: 100%;
            min-height: 600px;
            margin: 20px 0;
        }

        .chord-tooltip {
            position: absolute;
            padding: 8px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 4px;
            pointer-events: none;
            font-size: 14px;
            z-index: 1000;
            display: none;
        }
        
        .chord-path {
            opacity: 0.8;
            stroke: #444;
            stroke-width: 0.5px;
            transition: opacity 0.3s;
        }
        
        .chord-path:hover {
            opacity: 1;
        }
        
        .chord-tick text {
            font-size: 12px;
            fill: #e0e0e0;
        }
        
        .chord-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            justify-content: center;
        }

        .chord-legend-item {
            display: flex;
            align-items: center;
            font-size: 12px;
        }

        .chord-legend-color {
            width: 15px;
            height: 15px;
            margin-right: 5px;
            border-radius: 3px;
        }
        
        .panel {
            background-color: #1e1e1e;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #333;
            width: 100%;
        }
        
        .filter-section {
            background: #252525;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #444;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
            max-width: 33.33%;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #bbbbbb;
            font-weight: 500;
        }
        
        input, select {
            width: 100%;
            padding: 8px;
            background-color: #333;
            color: white;
            border: 1px solid #444;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }
        
        button {
            flex: 1;
            max-width: 150px;
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        button:hover {
            opacity: 0.85;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            min-width: 150px;
            background: #252525;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            text-align: center;
            border: 1px solid #444;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .stat-label {
            color: #bbbbbb;
        }
        
        .chart-container {
            margin: 20px 0;
            padding: 15px;
            background: #252525;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            border: 1px solid #444;
            width: 100%;
        }
        
        .table-container {
            width: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            max-height: 75vh;
            margin-bottom: 20px;
        }
        
        .related-table-section {
            margin-top: 40px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #252525;
            table-layout: auto;
        }
        
        thead th {
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: #2a3f5f;
            color: white;
        }
        
        th, td {
            padding: 12px 15px;
            border: 1px solid #444;
            text-align: left;
            word-wrap: break-word;
            max-width: 150px;
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
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        
        .status-aprovado { background: #4CAF50; }
        .status-rejeitado { background: #f44336; }
        .status-nao-aprovado { background: #ff9800; }
        .status-em-analise { background: #2196F3; }
        
        .score-bar {
            width: 80px;
            height: 10px;
            background: #444;
            border-radius: 5px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }
        
        .score-fill {
            height: 100%;
            border-radius: 5px;
            background: #4CAF50;
        }
        
        .btn-primary {
            background-color: #1976d2;
        }
        
        .btn-export {
            background-color: #17a2b8;
        }
        
        .checkbox-column {
            width: 5%;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .participate-button {
                margin-top: 15px;
                width: 100%;
                text-align: center;
            }
            
            .logo-title-container {
                flex-direction: column;
                text-align: center;
            }
            
            .filter-group {
                max-width: 100%;
            }
            
            .button-group {
                flex-direction: column;
                align-items: center;
            }
            
            button {
                max-width: 100%;
                margin-bottom: 10px;
            }
            
            th, td {
                max-width: none;
                font-size: 12px;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .score-bar {
                width: 60px;
            }
            
            .checkbox-column {
                width: 8%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="header-container">
            <div class="logo-title-container">
                <img src="Logotipo_SC2C_Vertical-512x512.png" class="header-logo" alt="Logo Rede UFSC"/>
                <div class="title-container">
                    <h1>Rede UFSC em Aeronáutica e Espaço</h1>
                    <h2 class="subtitle">Taxonomia de Pesquisa - Consulta</h2>
                </div>
            </div>
            <a href="https://sc2c.ufsc.br/Approval_Module_SC2C/sc2c_module.php" target="_blank" class="participate-button">
                Participe da Indexação
            </a>
        </div>
        
        <div class="panel">
            <div class="filter-section">
                <h2>Filtros</h2>
                <form method="GET" id="filterForm">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Busca Geral</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="programa">Programa</label>
                            <select id="programa" name="programa">
                                <option value="">Todos</option>
                                <?php foreach ($programas as $programa): ?>
                                    <option value="<?php echo htmlspecialchars($programa); ?>" <?php echo $filters['programa'] == $programa ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($programa); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="area">Área</label>
                            <select id="area" name="area">
                                <option value="">Todas</option>
                                <?php foreach ($areasFiltro as $area): ?>
                                    <option value="<?php echo htmlspecialchars($area); ?>" <?php echo $filters['area'] == $area ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($area); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">Todos</option>
                                <?php foreach ($statusList as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $filters['status'] == $status ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="professor">Professor</label>
                            <select id="professor" name="professor">
                                <option value="" <?php echo empty($filters['professor']) ? 'selected' : ''; ?>>Todos</option>
                                <?php foreach ($professores as $prof): ?>
                                    <option value="<?php echo htmlspecialchars($prof); ?>" <?php echo $filters['professor'] == $prof ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prof); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="segmento">Segmento</label>
                            <select id="segmento" name="segmento">
                                <option value="" <?php echo empty($filters['segmento']) ? 'selected' : ''; ?>>Todos</option>
                                <?php foreach ($segmentos as $segmento): ?>
                                    <option value="<?php echo htmlspecialchars($segmento); ?>" <?php echo $filters['segmento'] == $segmento ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($segmento); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                        <button type="button" class="btn btn-default" onclick="window.location.href='?'">Limpar</button>
                    </div>
                </form>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['aprovados']; ?></div>
                    <div class="stat-label">Aprovados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['rejeitados']; ?></div>
                    <div class="stat-label">Rejeitados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['em_analise']; ?></div>
                    <div class="stat-label">Em Análise</div>
                </div>
            </div>
            
            <div class="chart-container">
                <h2>Relação Linhas de Pesquisa x Áreas</h2>
                <div id="chordDiagram" class="chord-container"></div>
                <div id="chordTooltip" class="chord-tooltip"></div>
                <div id="chordLegend" class="chord-legend"></div>
            </div>
            
            <h2>Resultados (<?php echo count($filteredData); ?> registros)</h2>
            <div class="table-container">
                <table id="approvalsTable">
                    <thead>
                        <tr>
                            <th class="checkbox-column"></th>
                            <th style="width: 17%;">Programa</th>
                            <th style="width: 17%;">Área</th>
                            <th style="width: 17%;">Linha de Pesquisa</th>
                            <th style="width: 11%;">Usuário</th>
                            <th style="width: 17%;">Segmento</th>
                            <th style="width: 11%;">Domínio</th>
                            <th style="width: 11%;">Subdomínio</th>
                            <th style="width: 7%;">Score</th>
                            <th style="width: 4%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredData as $index => $row): ?>
                            <tr data-segmento="<?php echo htmlspecialchars($row['Segmento'] ?? ''); ?>">
                                <td class="checkbox-column">
                                    <input type="checkbox" class="row-checkbox" data-index="<?php echo $index; ?>">
                                </td>
                                <td><?php echo isset($row['Programa']) ? htmlspecialchars($row['Programa']) : ''; ?></td>
                                <td><?php echo isset($row['Área']) ? htmlspecialchars($row['Área']) : ''; ?></td>
                                <td><?php echo isset($row['Linha de Pesquisa']) ? htmlspecialchars($row['Linha de Pesquisa']) : ''; ?></td>
                                <td><?php echo isset($row['Usuário']) ? htmlspecialchars($row['Usuário']) : ''; ?></td>
                                <td><?php echo isset($row['Segmento']) ? htmlspecialchars($row['Segmento']) : ''; ?></td>
                                <td><?php echo isset($row['Domínio']) ? htmlspecialchars($row['Domínio']) : ''; ?></td>
                                <td><?php echo formatSubdomain($row['Subdomínio'] ?? '', $row['Classificação'] ?? ''); ?></td>
                                <td>
                                    <?php if (isset($row['Score'])): ?>
                                        <div class="score-bar">
                                            <div class="score-fill" style="width: <?php echo ((float)$row['Score'] * 100); ?>%"></div>
                                        </div>
                                        <?php echo $row['Score']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($row['Status'])): ?>
                                        <?php
                                        $statusClass = 'status-' . strtolower(str_replace([' ', 'ã', 'õ'], ['-', 'a', 'o'], $row['Status']));
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $row['Status']; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($filteredData)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center;">Nenhum resultado encontrado com os filtros aplicados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="related-table-section">
                <h2>Linhas Relacionadas por Segmento</h2>
                <div class="filter-section">
                    <h3>Filtros para Linhas Relacionadas</h3>
                    <form id="relatedFilterForm">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="related_programa">Programa</label>
                                <select id="related_programa" name="related_programa">
                                    <option value="">Todos</option>
                                    <?php foreach ($programas as $programa): ?>
                                        <option value="<?php echo htmlspecialchars($programa); ?>">
                                            <?php echo htmlspecialchars($programa); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="related_area">Área</label>
                                <select id="related_area" name="related_area">
                                    <option value="">Todas</option>
                                    <?php foreach ($areasFiltro as $area): ?>
                                        <option value="<?php echo htmlspecialchars($area); ?>">
                                            <?php echo htmlspecialchars($area); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="related_status">Status</label>
                                <select id="related_status" name="related_status">
                                    <option value="">Todos</option>
                                    <?php foreach ($statusList as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="related_professor">Professor</label>
                                <select id="related_professor" name="related_professor">
                                    <option value="">Todos</option>
                                    <?php foreach ($professores as $prof): ?>
                                        <option value="<?php echo htmlspecialchars($prof); ?>">
                                            <?php echo htmlspecialchars($prof); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="related_segmento">Segmento</label>
                                <select id="related_segmento" name="related_segmento">
                                    <option value="">Todos</option>
                                    <?php foreach ($segmentos as $segmento): ?>
                                        <option value="<?php echo htmlspecialchars($segmento); ?>">
                                            <?php echo htmlspecialchars($segmento); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group"></div>
                        </div>
                        <div class="button-group">
                            <button type="button" class="btn btn-primary" onclick="applyRelatedFilters()">Aplicar Filtros</button>
                            <button type="button" class="btn btn-default" onclick="clearRelatedFilters()">Limpar</button>
                            <button type="button" class="btn btn-export" onclick="exportRelatedTableToExcel()">Exportar Excel</button>
                        </div>
                    </form>
                </div>
                <div class="table-container">
                    <table id="relatedTable">
                        <thead>
                            <tr>
                                <th style="width: 18%;">Programa</th>
                                <th style="width: 18%;">Área</th>
                                <th style="width: 18%;">Linha de Pesquisa</th>
                                <th style="width: 12%;">Usuário</th>
                                <th style="width: 18%;">Segmento</th>
                                <th style="width: 12%;">Domínio</th>
                                <th style="width: 12%;">Subdomínio</th>
                                <th style="width: 8%;">Score</th>
                                <th style="width: 4%;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="relatedTableBody">
                            <tr>
                                <td colspan="9" style="text-align: center;">Nenhuma linha selecionada</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dados iniciais do chord diagram
        const chordData = {
            matrix: <?php echo json_encode($matrix); ?>,
            labels: <?php echo json_encode($allItems); ?>
        };

        // Dados completos do CSV para filtragem no lado do cliente
        const allData = <?php echo json_encode($data); ?>;

        // Cores para os grupos
        const colorScheme = d3.scaleOrdinal(d3.schemeCategory10);

        // Função para renderizar o chord diagram
        function renderChordDiagram(data) {
            d3.select("#chordDiagram").html("");
            d3.select("#chordLegend").html("");
            
            if (data.matrix.length === 0 || data.labels.length === 0) {
                console.warn("Sem dados para renderizar o chord diagram");
                return;
            }
            
            const width = document.getElementById("chordDiagram").clientWidth;
            const height = Math.min(width, 600);
            const outerRadius = Math.min(width, height) * 0.5 - 40;
            const innerRadius = outerRadius - 30;
            
            const chord = d3.chord()
                .padAngle(0.05)
                .sortSubgroups(d3.descending);
            
            const arc = d3.arc()
                .innerRadius(innerRadius)
                .outerRadius(outerRadius);
            
            const ribbon = d3.ribbon()
                .radius(innerRadius);
            
            const svg = d3.select("#chordDiagram").append("svg")
                .attr("width", width)
                .attr("height", height)
                .attr("viewBox", [-width / 2, -height / 2, width, height])
                .attr("style", "max-width: 100%; height: auto;");
            
            const g = svg.append("g");
            const chords = chord(data.matrix);
            
            const group = g.append("g")
                .selectAll("g")
                .data(chords.groups)
                .join("g");
            
            group.append("path")
                .attr("fill", d => colorScheme(d.index))
                .attr("d", arc)
                .on("mouseover", function(d) {
                    d3.select(this).attr("stroke", "#fff").attr("stroke-width", 2);
                    ribbonGroup.filter(dd => dd.source.index === d.index || dd.target.index === d.index)
                        .attr("stroke", "#fff")
                        .attr("stroke-width", 2);
                })
                .on("mouseout", function(d) {
                    d3.select(this).attr("stroke", null);
                    ribbonGroup.attr("stroke", null);
                });
            
            group.append("text")
                .each(d => { d.angle = (d.startAngle + d.endAngle) / 2; })
                .attr("dy", ".35em")
                .attr("transform", d => `
                    rotate(${d.angle * 180 / Math.PI - 90})
                    translate(${outerRadius + 10})
                    ${d.angle > Math.PI ? "rotate(180)" : ""}
                `)
                .attr("text-anchor", d => d.angle > Math.PI ? "end" : null)
                .text(d => data.labels[d.index])
                .style("font-size", "10px")
                .style("fill", "#e0e0e0");
            
            const ribbonGroup = g.append("g")
                .attr("fill-opacity", 0.8)
                .selectAll("path")
                .data(chords)
                .join("path")
                .attr("d", ribbon)
                .attr("fill", d => colorScheme(d.source.index))
                .attr("stroke", "#333")
                .on("mouseover", function(d) {
                    const tooltip = d3.select("#chordTooltip");
                    tooltip.style("display", "block")
                        .html(`
                            <strong>${data.labels[d.source.index]}</strong> → 
                            <strong>${data.labels[d.target.index]}</strong><br>
                            Valor: ${d.source.value}
                        `);
                })
                .on("mousemove", function() {
                    d3.select("#chordTooltip")
                        .style("left", (d3.event.pageX + 10) + "px")
                        .style("top", (d3.event.pageY - 10) + "px");
                })
                .on("mouseout", function() {
                    d3.select("#chordTooltip").style("display", "none");
                });
            
            const legend = d3.select("#chordLegend");
            legend.selectAll(".chord-legend-item")
                .data(data.labels)
                .join("div")
                .attr("class", "chord-legend-item")
                .html((d, i) => `
                    <span class="chord-legend-color" style="background:${colorScheme(i)}"></span>
                    ${d}
                `);
        }

        // Função para atualizar o chord com filtros
        function updateChordDiagram() {
            const filters = {
                programa: document.getElementById("programa").value,
                area: document.getElementById("area").value,
                status: document.getElementById("status").value,
                professor: document.getElementById("professor").value,
                segmento: document.getElementById("segmento").value
            };
            
            fetch(`?${new URLSearchParams(filters)}`)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const script = doc.querySelector('script:last-of-type');
                    if (script) {
                        const match = script.textContent.match(/const chordData = ({[\s\S]*?});/);
                        if (match) {
                            const newChordData = JSON.parse(match[1]);
                            renderChordDiagram(newChordData);
                        }
                    }
                })
                .catch(error => console.error('Erro ao atualizar chord diagram:', error));
        }

        // Função para aplicar filtros na tabela relacionada
        function applyRelatedFilters() {
            updateRelatedTable();
        }

        // Função para limpar filtros da tabela relacionada
        function clearRelatedFilters() {
            document.getElementById('related_programa').value = '';
            document.getElementById('related_area').value = '';
            document.getElementById('related_status').value = '';
            document.getElementById('related_professor').value = '';
            document.getElementById('related_segmento').value = '';
            updateRelatedTable();
        }

        // Função para atualizar a tabela de linhas relacionadas
        function updateRelatedTable() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const selectedSegments = new Set();
            
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const segmento = row.dataset.segmento;
                if (segmento) {
                    selectedSegments.add(segmento);
                }
            });
            
            const relatedTableBody = document.getElementById('relatedTableBody');
            relatedTableBody.innerHTML = '';
            
            // Obter valores dos filtros da tabela relacionada
            const relatedFilters = {
                programa: document.getElementById('related_programa').value,
                area: document.getElementById('related_area').value,
                status: document.getElementById('related_status').value,
                professor: document.getElementById('related_professor').value,
                segmento: document.getElementById('related_segmento').value
            };
            
            let relatedRows = allData.filter(row => 
                row['Status'] !== 'Em Análise' && 
                (selectedSegments.size === 0 || selectedSegments.has(row['Segmento']))
            );
            
            // Aplicar filtros adicionais
            if (relatedFilters.programa) {
                relatedRows = relatedRows.filter(row => row['Programa'] === relatedFilters.programa);
            }
            if (relatedFilters.area) {
                relatedRows = relatedRows.filter(row => row['Área'] === relatedFilters.area);
            }
            if (relatedFilters.status) {
                relatedRows = relatedRows.filter(row => row['Status'] === relatedFilters.status);
            }
            if (relatedFilters.professor) {
                relatedRows = relatedRows.filter(row => row['Usuário'] === relatedFilters.professor);
            }
            if (relatedFilters.segmento) {
                relatedRows = relatedRows.filter(row => row['Segmento'] === relatedFilters.segmento);
            }
            
            if (relatedRows.length === 0) {
                relatedTableBody.innerHTML = '<tr><td colspan="9" style="text-align: center;">Nenhum resultado encontrado com os filtros aplicados</td></tr>';
                return relatedRows;
            }
            
            relatedRows.forEach(row => {
                const statusClass = row['Status'] ? 
                    'status-' + row['Status'].toLowerCase().replace(/[\sãõ]/g, match => match === 'ã' ? 'a' : match === 'õ' ? 'o' : '-') : '';
                
                const scoreHtml = row['Score'] ? `
                    <div class="score-bar">
                        <div class="score-fill" style="width: ${parseFloat(row['Score']) * 100}%"></div>
                    </div>
                    ${row['Score']}
                ` : '';
                
                const subdominio = row['Subdomínio'] && row['Classificação'] ? 
                    `${row['Subdomínio'].trim()}: ${row['Classificação'].replace(/^(ACARE|NASA)\s*(Manual\s*)?\d+:/i, '').trim()}` : 
                    '';
                
                relatedTableBody.innerHTML += `
                    <tr>
                        <td>${row['Programa'] || ''}</td>
                        <td>${row['Área'] || ''}</td>
                        <td>${row['Linha de Pesquisa'] || ''}</td>
                        <td>${row['Usuário'] || ''}</td>
                        <td>${row['Segmento'] || ''}</td>
                        <td>${row['Domínio'] || ''}</td>
                        <td>${subdominio}</td>
                        <td>${scoreHtml}</td>
                        <td><span class="status-badge ${statusClass}">${row['Status'] || ''}</span></td>
                    </tr>
                `;
            });
            
            return relatedRows;
        }

        // Função para exportar a tabela relacionada para Excel
        function exportRelatedTableToExcel() {
            // Obter os filtros atuais
            const relatedFilters = {
                programa: document.getElementById('related_programa').value,
                area: document.getElementById('related_area').value,
                status: document.getElementById('related_status').value,
                professor: document.getElementById('related_professor').value,
                segmento: document.getElementById('related_segmento').value
            };
            
            // Obter checkboxes selecionadas
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const selectedSegments = new Set();
            
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const segmento = row.dataset.segmento;
                if (segmento) {
                    selectedSegments.add(segmento);
                }
            });
            
            // Filtrar os dados diretamente
            let relatedRows = allData.filter(row => 
                row['Status'] !== 'Em Análise' && 
                (selectedSegments.size === 0 || selectedSegments.has(row['Segmento']))
            );
            
            // Aplicar filtros adicionais
            if (relatedFilters.programa) {
                relatedRows = relatedRows.filter(row => row['Programa'] === relatedFilters.programa);
            }
            if (relatedFilters.area) {
                relatedRows = relatedRows.filter(row => row['Área'] === relatedFilters.area);
            }
            if (relatedFilters.status) {
                relatedRows = relatedRows.filter(row => row['Status'] === relatedFilters.status);
            }
            if (relatedFilters.professor) {
                relatedRows = relatedRows.filter(row => row['Usuário'] === relatedFilters.professor);
            }
            if (relatedFilters.segmento) {
                relatedRows = relatedRows.filter(row => row['Segmento'] === relatedFilters.segmento);
            }
            
            if (!relatedRows || relatedRows.length === 0) {
                alert('Nenhuma linha disponível para exportação.');
                return;
            }
            
            // Preparar dados para exportação
            const exportData = relatedRows.map(row => ({
                Programa: row['Programa'] || '',
                Área: row['Área'] || '',
                'Linha de Pesquisa': row['Linha de Pesquisa'] || '',
                Usuário: row['Usuário'] || '',
                Segmento: row['Segmento'] || '',
                Domínio: row['Domínio'] || '',
                Subdomínio: row['Subdomínio'] && row['Classificação'] ? 
                    `${row['Subdomínio'].trim()}: ${row['Classificação'].replace(/^(ACARE|NASA)\s*(Manual\s*)?\d+:/i, '').trim()}` : '',
                Score: row['Score'] || '',
                Status: row['Status'] || ''
            }));
            
            // Criar planilha
            const worksheet = XLSX.utils.json_to_sheet(exportData);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Linhas Relacionadas');
            
            // Gerar nome do arquivo com data
            const today = new Date();
            const dateString = today.toISOString().split('T')[0];
            const fileName = `linhas_relacionadas_${dateString}.xlsx`;
            
            // Exportar arquivo
            XLSX.writeFile(workbook, fileName);
        }

        // Renderiza o gráfico inicial
        document.addEventListener("DOMContentLoaded", () => {
            renderChordDiagram(chordData);
            
            // Adiciona evento às checkboxes
            document.querySelectorAll('.row-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateRelatedTable);
            });
        });

        // Adiciona evento para atualizar o gráfico e resetar checkboxes quando o formulário principal for enviado
        document.getElementById("filterForm").addEventListener("submit", (e) => {
            e.preventDefault();
            updateChordDiagram();
            // Resetar checkboxes e tabela relacionada após aplicar filtros
            document.querySelectorAll('.row-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateRelatedTable();
            e.target.submit();
        });
    </script>
</body>
</html>
