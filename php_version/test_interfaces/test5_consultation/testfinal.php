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
    $encoding = mb_detect_encoding($content, array('UTF-8', 'ISO-8859-1', 'Windows-1252'), true);
    
    if ($encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding ? $encoding : 'auto');
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
$csvFile = 'final_aprovações_28_07_2025.csv';
$data = file_exists($csvFile) ? readCSV($csvFile) : array();

// Filtrar apenas aprovados
$data = array_filter($data, function($row) {
    return isset($row['Status']) && strtolower(trim($row['Status'])) === 'aprovado';
});

// Aplicar filtros
$filters = array(
    'search' => isset($_GET['search']) ? $_GET['search'] : '',
    'programa' => isset($_GET['programa']) ? $_GET['programa'] : '',
    'area' => isset($_GET['area']) ? $_GET['area'] : '',
    'segmento' => isset($_GET['segmento']) ? $_GET['segmento'] : ''
);

$filteredData = $data;
foreach ($filters as $field => $value) {
    if (!empty($value)) {
        if ($field == 'search') {
            $filteredData = filterByTerm($filteredData, $value);
        } else {
            $fieldName = ($field == 'segmento' ? 'Segmento' : ($field == 'area' ? 'Área' : ucfirst($field)));
            $filteredData = filterByField($filteredData, $fieldName, $value);
        }
    }
}

// Preparar dados para os gráficos de corda
// Gráfico 1: Programa x Área de Taxonomia (Segmento no CSV)
$programaSegmentoRelations = array();
foreach ($filteredData as $row) {
    if (!isset($row['Programa']) || !isset($row['Segmento'])) continue;
    
    $programa = $row['Programa'];
    $segmento = $row['Segmento'];
    
    if (!isset($programaSegmentoRelations[$programa])) {
        $programaSegmentoRelations[$programa] = array();
    }
    $programaSegmentoRelations[$programa][$segmento] = isset($programaSegmentoRelations[$programa][$segmento]) ? $programaSegmentoRelations[$programa][$segmento] + 1 : 1;
}

$programasUnicos = array_keys($programaSegmentoRelations);
$segmentosUnicos = array_keys(call_user_func_array('array_merge', array_values($programaSegmentoRelations)));
$allItemsProgramaSegmento = array_values(array_unique(array_merge($programasUnicos, $segmentosUnicos)));

$matrixProgramaSegmento = array_fill(0, count($allItemsProgramaSegmento), array_fill(0, count($allItemsProgramaSegmento), 0));

foreach ($programaSegmentoRelations as $programa => $relations) {
    $i = array_search($programa, $allItemsProgramaSegmento);
    foreach ($relations as $segmento => $count) {
        $j = array_search($segmento, $allItemsProgramaSegmento);
        $matrixProgramaSegmento[$i][$j] += $count;
        $matrixProgramaSegmento[$j][$i] += $count;
    }
}

// Gráfico 2: Área de Concentração (Área no CSV) x Área de Taxonomia (Segmento no CSV)
$areaSegmentoRelations = array();
foreach ($filteredData as $row) {
    if (!isset($row['Área']) || !isset($row['Segmento'])) continue;
    
    $areaConcentracao = $row['Área'];
    $segmento = $row['Segmento'];
    
    if (!isset($areaSegmentoRelations[$areaConcentracao])) {
        $areaSegmentoRelations[$areaConcentracao] = array();
    }
    $areaSegmentoRelations[$areaConcentracao][$segmento] = isset($areaSegmentoRelations[$areaConcentracao][$segmento]) ? $areaSegmentoRelations[$areaConcentracao][$segmento] + 1 : 1;
}

$areasConcentracaoUnicas = array_keys($areaSegmentoRelations);
$allItemsAreaSegmento = array_values(array_unique(array_merge($areasConcentracaoUnicas, $segmentosUnicos)));

$matrixAreaSegmento = array_fill(0, count($allItemsAreaSegmento), array_fill(0, count($allItemsAreaSegmento), 0));

foreach ($areaSegmentoRelations as $areaConcentracao => $relations) {
    $i = array_search($areaConcentracao, $allItemsAreaSegmento);
    foreach ($relations as $segmento => $count) {
        $j = array_search($segmento, $allItemsAreaSegmento);
        $matrixAreaSegmento[$i][$j] += $count;
        $matrixAreaSegmento[$j][$i] += $count;
    }
}

// Obter valores únicos para filtros
$programas = array();
$areasConcentracao = array();
$areasTaxonomia = array();

foreach ($data as $row) {
    if (isset($row['Programa']) && !in_array($row['Programa'], $programas) && !empty($row['Programa'])) {
        $programas[] = $row['Programa'];
    }
    if (isset($row['Área']) && !in_array($row['Área'], $areasConcentracao) && !empty($row['Área'])) {
        $areasConcentracao[] = $row['Área'];
    }
    if (isset($row['Segmento']) && !in_array($row['Segmento'], $areasTaxonomia) && !empty($row['Segmento'])) {
        $areasTaxonomia[] = $row['Segmento'];
    }
}

sort($programas);
sort($areasConcentracao);
sort($areasTaxonomia);

// Função para formatar Subdomínio
function formatSubdomain($subdomain, $classification) {
    $value = trim($classification);
    $value = preg_replace('/^(ACARE|NASA)\s*(Manual\s*)?\d+:/i', '', $value);
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
            margin-bottom: 100px; /* Espaço reservado para o footer */
        }
        
        .container-fluid {
            width: 100%;
            max-width: none;
            padding: 0 15px;
        }
        
        .instructions-panel {
            background-color: #1e1e1e;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #333;
        }

        .instructions-panel h3 {
            color: #4a86e8;
            margin-top: 0;
            margin-bottom: 20px;
            text-align: center;
        }

        .instructions-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .instruction-column {
            flex: 1;
            min-width: 300px;
            padding: 0 10px;
            margin-bottom: 15px;
        }

        .instruction-card {
            background-color: #252525;
            border-radius: 6px;
            padding: 15px;
            height: 100%;
            border: 1px solid #444;
        }

        .instruction-card h4 {
            color: #4a86e8;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
            border-bottom: 1px solid #444;
            padding-bottom: 8px;
        }

        .instruction-card ul {
            padding-left: 20px;
            margin-bottom: 0;
        }

        .instruction-card li {
            margin-bottom: 8px;
            color: #d0d0d0;
        }

        .instruction-card li:last-child {
            margin-bottom: 0;
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
            color: #4a86e8;
            margin-top: 0;
        }
        
        .participate-button {
            background-color: #1976d2;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #4a86e8;
        }
        
        .participate-button:hover {
            background-color: #4a86e8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
            text-decoration: none;
            color: white;
        }
        
        .participate-button svg {
            width: 20px;
            height: 20px;
            fill: white;
        }
        
        .chord-container {
            width: 100%;
            min-height: 600px;
            margin: 20px 0;
            background: #252525;
            border-radius: 5px;
            padding: 15px;
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
            background: #252525;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #444;
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
        }

        .legend-section {
            flex: 1;
            min-width: 300px;
        }

        .legend-section h4 {
            color: #4a86e8;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .chord-legend-item {
            display: flex;
            align-items: center;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .chord-legend-color {
            width: 15px;
            height: 15px;
            margin-right: 8px;
            border-radius: 3px;
            border: 1px solid #fff;
        }

        #segmentosLegend1, #segmentosLegend2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
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
            background: #1a1a1a;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #333;
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
            overflow-x: auto;
            max-height: 75vh;
            margin-bottom: 20px;
        }
        
        .table-container::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #1a1a1a;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 6px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        .table-container {
            scrollbar-color: #333 #1a1a1a;
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
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tab {
            display: inline-block;
            padding: 10px 20px;
            background: #333;
            color: #e0e0e0;
            cursor: pointer;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
        }
        
        .tab.active {
            background: #4a86e8;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Footer Styles */
        .body_footer {
            background-color: #1e1e1e;
            border-top: 1px solid #333;
            padding: 20px 0;
            width: 100%;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.5);
        }
        
        .footer_container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .footer_logo_section {
            flex: 1;
            min-width: 150px;
            text-align: center;
        }
        
        .footer_logo {
            width: 100px;
            height: auto;
        }
        
        .footer_info {
            flex: 2;
            min-width: 200px;
            color: #d0d0d0;
        }
        
        .footer_info h4 {
            color: #4a86e8;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .footer_info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .footer_info a {
            color: #4a86e8;
            text-decoration: none;
        }
        
        .footer_info a:hover {
            color: #ffffff;
            text-decoration: underline;
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
                justify-content: center;
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
            
            .chord-legend {
                flex-direction: column;
                gap: 10px;
            }
            
            .legend-section {
                min-width: 100%;
            }
            
            #segmentosLegend1, #segmentosLegend2 {
                grid-template-columns: 1fr;
            }
            
            .footer_container {
                flex-direction: column;
                text-align: center;
            }
            
            .footer_logo_section {
                margin-bottom: 15px;
            }
            
            .footer_info {
                text-align: center;
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
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm4.59-12.42L10 14.17l-2.59-2.58L6 13l4 4 8-8z"/>
                </svg>
                Participe da Indexação
            </a>
        </div>
        
        <div class="instructions-panel">
            <h3>Instruções de Uso</h3>
            <div class="instructions-row">
                <div class="instruction-column">
                    <div class="instruction-card">
                        <h4>1. Filtragem de Dados</h4>
                        <ul>
                            <li>Utilize os filtros para refinar os registros aprovados</li>
                            <li>Escolha Programa, Área de Concentração ou Área de Taxonomia</li>
                            <li>Use a Busca Geral para pesquisar termos em qualquer campo</li>
                        </ul>
                    </div>
                </div>
                <div class="instruction-column">
                    <div class="instruction-card">
                        <h4>2. Visualização de Relacionamentos</h4>
                        <ul>
                            <li>Explore os gráficos de corda em duas abas:</li>
                            <li>Aba 1: Programas de Pós-Graduação x Áreas de Taxonomia</li>
                            <li>Aba 2: Áreas de Concentração x Áreas de Taxonomia</li>
                            <li>Passe o mouse sobre as conexões para ver detalhes</li>
                        </ul>
                    </div>
                </div>
                <div class="instruction-column">
                    <div class="instruction-card">
                        <h4>3. Exportação de Dados</h4>
                        <ul>
                            <li>Aplique filtros para personalizar os resultados</li>
                            <li>Clique em "Exportar Excel" para baixar a tabela filtrada</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="panel">
            <div class="filter-section">
                <h2>Filtros</h2>
                <form id="filterForm">
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
                            <label for="area">Área de Concentração</label>
                            <select id="area" name="area">
                                <option value="">Todas</option>
                                <?php foreach ($areasConcentracao as $area): ?>
                                    <option value="<?php echo htmlspecialchars($area); ?>" <?php echo $filters['area'] == $area ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($area); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="segmento">Área de Taxonomia</label>
                            <select id="segmento" name="segmento">
                                <option value="">Todas</option>
                                <?php foreach ($areasTaxonomia as $segmento): ?>
                                    <option value="<?php echo htmlspecialchars($segmento); ?>" <?php echo $filters['segmento'] == $segmento ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($segmento); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group"></div>
                        <div class="filter-group"></div>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                        <button type="button" class="btn btn-default" onclick="clearFilters()">Limpar</button>
                        <button type="button" class="btn btn-export" onclick="exportTableToExcel()">Exportar Excel</button>
                    </div>
                </form>
            </div>
            
            <div class="tab-container">
                <div class="tab active" onclick="showTab('programaSegmento')">Programas x Áreas de Taxonomia</div>
                <div class="tab" onclick="showTab('areaSegmento')">Áreas de Concentração x Áreas de Taxonomia</div>
            </div>
            
            <div id="programaSegmento" class="tab-content active">
                <div class="chart-container">
                    <h2>Relação Programas x Áreas de Taxonomia</h2>
                    <div id="chordDiagram1" class="chord-container"></div>
                    <div id="chordTooltip1" class="chord-tooltip"></div>
                    <div id="chordLegend1" class="chord-legend">
                        <div class="legend-section">
                            <h4>Programas de Pós-Graduação</h4>
                            <div id="programasLegend1"></div>
                        </div>
                        <div class="legend-section">
                            <h4>Áreas de Taxonomia</h4>
                            <div id="segmentosLegend1"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="areaSegmento" class="tab-content">
                <div class="chart-container">
                    <h2>Relação Áreas de Concentração x Áreas de Taxonomia</h2>
                    <div id="chordDiagram2" class="chord-container"></div>
                    <div id="chordTooltip2" class="chord-tooltip"></div>
                    <div id="chordLegend2" class="chord-legend">
                        <div class="legend-section">
                            <h4>Áreas de Concentração</h4>
                            <div id="areasLegend2"></div>
                        </div>
                        <div class="legend-section">
                            <h4>Áreas de Taxonomia</h4>
                            <div id="segmentosLegend2"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <h2>Resultados (<span id="resultCount"><?php echo count($filteredData); ?></span> registros)</h2>
            <div class="table-container">
                <table id="approvalsTable">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Programa</th>
                            <th style="width: 20%;">Área de Concentração</th>
                            <th style="width: 20%;">Linha de Pesquisa</th>
                            <th style="width: 20%;">Área de Taxonomia</th>
                            <th style="width: 10%;">Domínio</th>
                            <th style="width: 10%;">Subdomínio</th>
                            <th style="width: 10%;">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredData as $row): ?>
                            <tr>
                                <td><?php echo isset($row['Programa']) ? htmlspecialchars($row['Programa']) : ''; ?></td>
                                <td><?php echo isset($row['Área']) ? htmlspecialchars($row['Área']) : ''; ?></td>
                                <td><?php echo isset($row['Linha de Pesquisa']) ? htmlspecialchars($row['Linha de Pesquisa']) : ''; ?></td>
                                <td><?php echo isset($row['Segmento']) ? htmlspecialchars($row['Segmento']) : ''; ?></td>
                                <td><?php echo isset($row['Domínio']) ? htmlspecialchars($row['Domínio']) : ''; ?></td>
                                <td><?php echo formatSubdomain($row['Subdomínio'] ? $row['Subdomínio'] : '', $row['Classificação'] ? $row['Classificação'] : ''); ?></td>
                                <td>
                                    <?php if (isset($row['Score'])): ?>
                                        <div class="score-bar">
                                            <div class="score-fill" style="width: <?php echo ((float)$row['Score'] * 100); ?>%"></div>
                                        </div>
                                        <?php echo htmlspecialchars($row['Score']); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($filteredData)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">Nenhum resultado encontrado com os filtros aplicados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer class="body_footer">
        <div class="footer_container">
            <div class="footer_logo_section">
                <a href="https://ufsc.br/" target="_blank" rel="noopener">
                    <img class="footer_logo" src="ufsc_logo.png" alt="Novo Logo UFSC">
                </a>
            </div>
            <div class="footer_info">
                <h4>SC2C.Aero</h4>
                <p>Santa Catarina's Center of Convergence for Aerospace Technologies</p>
                <p><strong>Phone:</strong> +55 48 3721-4607</p>
                <p><strong>Address:</strong> R. Eng. Agronômico Andrei Cristian Ferreira, s/n, CEP 88040-900</p>
                <p><strong>Email:</strong> <a href="mailto:sc2c@contato.ufsc.br">sc2c@contato.ufsc.br</a></p>
            </div>
        </div>
    </footer>

    <script>
        // Dados iniciais dos chord diagrams e tabela
        var chordData1 = {
            matrix: <?php echo json_encode($matrixProgramaSegmento); ?>,
            labels: <?php echo json_encode($allItemsProgramaSegmento); ?>,
            programas: <?php echo json_encode($programasUnicos); ?>,
            areasTaxonomia: <?php echo json_encode($segmentosUnicos); ?>
        };

        var chordData2 = {
            matrix: <?php echo json_encode($matrixAreaSegmento); ?>,
            labels: <?php echo json_encode($allItemsAreaSegmento); ?>,
            areasConcentracao: <?php echo json_encode($areasConcentracaoUnicas); ?>,
            areasTaxonomia: <?php echo json_encode($segmentosUnicos); ?>
        };

        var filteredData = <?php echo json_encode($filteredData); ?>;

        // Cores para as áreas de taxonomia
        var colorScheme = d3.scaleOrdinal(d3.schemeCategory10);
        var programaColor = '#e0e0e0'; // Cor neutra para programas e áreas de concentração

        // Função para renderizar o chord diagram
        function renderChordDiagram(containerId, tooltipId, legend1Id, legend2Id, data, isProgramaSegmento) {
            d3.select('#' + containerId).html("");
            d3.select('#' + legend1Id).html("");
            d3.select('#' + legend2Id).html("");
            
            if (!data.matrix || data.matrix.length === 0 || !data.labels || data.labels.length === 0) {
                console.warn('Sem dados válidos para renderizar o chord diagram em ' + containerId);
                d3.select('#' + containerId).append("p")
                    .style("text-align", "center")
                    .style("color", "#e0e0e0")
                    .text("Nenhum dado disponível para visualização.");
                return;
            }
            
            var width = document.getElementById(containerId).clientWidth;
            var height = Math.min(width, 600);
            var outerRadius = Math.min(width, height) * 0.5 - 40;
            var innerRadius = outerRadius - 30;
            
            var chord = d3.chord()
                .padAngle(0.05)
                .sortSubgroups(d3.descending);
            
            var arc = d3.arc()
                .innerRadius(innerRadius)
                .outerRadius(outerRadius);
            
            var ribbon = d3.ribbon()
                .radius(innerRadius);
            
            var svg = d3.select('#' + containerId).append("svg")
                .attr("width", width)
                .attr("height", height)
                .attr("viewBox", [-width / 2, -height / 2, width, height])
                .attr("style", "max-width: 100%; height: auto;");
            
            var g = svg.append("g");
            var chords = chord(data.matrix);
            
            var group = g.append("g")
                .selectAll("g")
                .data(chords.groups)
                .join("g");
            
            group.append("path")
                .attr("fill", function(d) {
                    var label = data.labels[d.index];
                    return (isProgramaSegmento ? data.programas : data.areasConcentracao).indexOf(label) !== -1 ? 
                           programaColor : colorScheme(data.areasTaxonomia.indexOf(label));
                })
                .attr("d", arc)
                .on("mouseover", function(d) {
                    d3.select(this).attr("stroke", "#fff").attr("stroke-width", 2);
                    ribbonGroup.filter(function(dd) { return dd.source.index === d.index || dd.target.index === d.index; })
                        .attr("stroke", "#fff")
                        .attr("stroke-width", 2);
                })
                .on("mouseout", function(d) {
                    d3.select(this).attr("stroke", null);
                    ribbonGroup.attr("stroke", null);
                });
            
            group.append("text")
                .each(function(d) { d.angle = (d.startAngle + d.endAngle) / 2; })
                .attr("dy", ".35em")
                .attr("transform", function(d) {
                    return "rotate(" + (d.angle * 180 / Math.PI - 90) + ")" +
                           "translate(" + (outerRadius + 10) + ")" +
                           (d.angle > Math.PI ? "rotate(180)" : "");
                })
                .attr("text-anchor", function(d) { return d.angle > Math.PI ? "end" : null; })
                .text(function(d) { return data.labels[d.index]; })
                .style("font-size", "10px")
                .style("fill", "#e0e0e0");
            
            var ribbonGroup = g.append("g")
                .attr("fill-opacity", 0.8)
                .selectAll("path")
                .data(chords)
                .join("path")
                .attr("d", ribbon)
                .attr("fill", function(d) {
                    var sourceLabel = data.labels[d.source.index];
                    var targetLabel = data.labels[d.target.index];
                    return data.areasTaxonomia.indexOf(sourceLabel) !== -1 ? colorScheme(data.areasTaxonomia.indexOf(sourceLabel)) :
                           data.areasTaxonomia.indexOf(targetLabel) !== -1 ? colorScheme(data.areasTaxonomia.indexOf(targetLabel)) : programaColor;
                })
                .attr("stroke", "#333")
                .on("mouseover", function(d) {
                    var tooltip = d3.select('#' + tooltipId);
                    tooltip.style("display", "block")
                        .html(
                            "<strong>" + data.labels[d.source.index] + "</strong> → " + 
                            "<strong>" + data.labels[d.target.index] + "</strong><br>" +
                            "Valor: " + d.source.value
                        );
                })
                .on("mousemove", function() {
                    d3.select('#' + tooltipId)
                        .style("left", (d3.event.pageX + 10) + "px")
                        .style("top", (d3.event.pageY - 10) + "px");
                })
                .on("mouseout", function() {
                    d3.select('#' + tooltipId).style("display", "none");
                });
            
            // Renderizar legenda
            var legend1 = d3.select('#' + legend1Id);
            var items = isProgramaSegmento ? data.programas : data.areasConcentracao;
            legend1.selectAll(".chord-legend-item")
                .data(items)
                .join("div")
                .attr("class", "chord-legend-item")
                .html(function(d) {
                    return '<span class="chord-legend-color" style="background:' + programaColor + '"></span>' + d;
                });
            
            var legend2 = d3.select('#' + legend2Id);
            legend2.selectAll(".chord-legend-item")
                .data(data.areasTaxonomia)
                .join("div")
                .attr("class", "chord-legend-item")
                .html(function(d, i) {
                    return '<span class="chord-legend-color" style="background:' + colorScheme(i) + '"></span>' + d;
                });
        }

        // Função para mostrar/esconder abas
        function showTab(tabId) {
            var tabs = document.querySelectorAll('.tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            var contents = document.querySelectorAll('.tab-content');
            for (var i = 0; i < contents.length; i++) {
                contents[i].classList.remove('active');
            }
            document.querySelector('.tab[onclick="showTab(\'' + tabId + '\')"]').classList.add('active');
            document.getElementById(tabId).classList.add('active');
            if (tabId === 'programaSegmento') {
                renderChordDiagram('chordDiagram1', 'chordTooltip1', 'programasLegend1', 'segmentosLegend1', chordData1, true);
            } else {
                renderChordDiagram('chordDiagram2', 'chordTooltip2', 'areasLegend2', 'segmentosLegend2', chordData2, false);
            }
        }

        // Função para limpar filtros
        function clearFilters() {
            document.getElementById('filterForm').reset();
            applyFilters();
        }

        // Função para aplicar filtros via AJAX
        function applyFilters() {
            var filters = {
                search: document.getElementById("search").value,
                programa: document.getElementById("programa").value,
                area: document.getElementById("area").value,
                segmento: document.getElementById("segmento").value
            };
            
            var queryString = Object.keys(filters).map(function(key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(filters[key]);
            }).join('&');
            
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '?' + queryString, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var script = doc.querySelector('script:last-of-type');
                    if (script) {
                        var match1 = script.textContent.match(/var chordData1 = ({[\s\S]*?});/);
                        var match2 = script.textContent.match(/var chordData2 = ({[\s\S]*?});/);
                        var match3 = script.textContent.match(/var filteredData = (\[[\s\S]*?\]);/);
                        if (match1 && match2 && match3) {
                            chordData1 = JSON.parse(match1[1]);
                            chordData2 = JSON.parse(match2[1]);
                            filteredData = JSON.parse(match3[1]);
                            
                            // Atualizar tabela
                            var tbody = document.querySelector('#approvalsTable tbody');
                            tbody.innerHTML = '';
                            if (filteredData.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Nenhum resultado encontrado com os filtros aplicados</td></tr>';
                            } else {
                                filteredData.forEach(function(row) {
                                    var score = row['Score'] ? parseFloat(row['Score']) : 0;
                                    var subdominio = (row['Subdomínio'] && row['Classificação']) ? 
                                        row['Subdomínio'].trim() + ': ' + row['Classificação'].replace(/^(ACARE|NASA)\s*(Manual\s*)?\d+:/i, '').trim() : 
                                        (row['Subdomínio'] || '');
                                    
                                    var tr = document.createElement('tr');
                                    tr.innerHTML = `
                                        <td>${row['Programa'] || ''}</td>
                                        <td>${row['Área'] || ''}</td>
                                        <td>${row['Linha de Pesquisa'] || ''}</td>
                                        <td>${row['Segmento'] || ''}</td>
                                        <td>${row['Domínio'] || ''}</td>
                                        <td>${subdominio}</td>
                                        <td>
                                            ${row['Score'] ? `
                                                <div class="score-bar">
                                                    <div class="score-fill" style="width: ${score * 100}%"></div>
                                                </div>
                                                ${row['Score']}
                                            ` : ''}
                                        </td>
                                    `;
                                    tbody.appendChild(tr);
                                });
                            }
                            
                            // Atualizar contador de resultados
                            document.getElementById('resultCount').textContent = filteredData.length;
                            
                            // Atualizar chord diagrams
                            var activeTab = document.querySelector('.tab-content.active').id;
                            if (activeTab === 'programaSegmento') {
                                renderChordDiagram('chordDiagram1', 'chordTooltip1', 'programasLegend1', 'segmentosLegend1', chordData1, true);
                            } else {
                                renderChordDiagram('chordDiagram2', 'chordTooltip2', 'areasLegend2', 'segmentosLegend2', chordData2, false);
                            }
                        }
                    }
                }
            };
            xhr.onerror = function() {
                console.error('Erro ao aplicar filtros');
            };
            xhr.send();
        }

        // Função para exportar a tabela filtrada para Excel com formatação
        function exportTableToExcel() {
            if (filteredData.length === 0) {
                alert('Nenhuma linha disponível para exportação.');
                return;
            }
            
            // Criar array de dados para exportação
            var exportData = [
                ["Programa", "Área de Concentração", "Linha de Pesquisa", "Área de Taxonomia", "Domínio", "Subdomínio", "Score"]
            ];
            
            // Adicionar linhas filtradas
            filteredData.forEach(function(row) {
                var subdominio = (row['Subdomínio'] && row['Classificação']) ? 
                    row['Subdomínio'].trim() + ': ' + row['Classificação'].replace(/^(ACARE|NASA)\s*(Manual\s*)?\d+:/i, '').trim() : 
                    (row['Subdomínio'] || '');
                
                exportData.push([
                    row['Programa'] || '',
                    row['Área'] || '',
                    row['Linha de Pesquisa'] || '',
                    row['Segmento'] || '',
                    row['Domínio'] || '',
                    subdominio,
                    row['Score'] || ''
                ]);
            });
            
            // Criar planilha Excel
            var worksheet = XLSX.utils.aoa_to_sheet(exportData);
            
            // Aplicar formatação
            var range = XLSX.utils.decode_range(worksheet['!ref']);
            for (var R = range.s.r; R <= range.e.r; ++R) {
                for (var C = range.s.c; C <= range.e.c; ++C) {
                    var cell_address = {c:C, r:R};
                    var cell_ref = XLSX.utils.encode_cell(cell_address);
                    if (!worksheet[cell_ref]) continue;
                    
                    // Estilo padrão
                    worksheet[cell_ref].s = {
                        font: { name: 'Arial', sz: 12 },
                        alignment: { vertical: 'center', wrapText: true },
                        border: {
                            top: { style: 'thin', color: { rgb: '444444' } },
                            bottom: { style: 'thin', color: { rgb: '444444' } },
                            left: { style: 'thin', color: { rgb: '444444' } },
                            right: { style: 'thin', color: { rgb: '444444' } }
                        }
                    };
                    
                    // Estilo do cabeçalho
                    if (R === 0) {
                        worksheet[cell_ref].s.fill = { fgColor: { rgb: '2a3f5f' } };
                        worksheet[cell_ref].s.font = { name: 'Arial', sz: 12, bold: true, color: { rgb: 'FFFFFF' } };
                    } else {
                        // Estilo de linhas alternadas
                        worksheet[cell_ref].s.fill = { fgColor: { rgb: R % 2 === 1 ? '252525' : '1e1e1e' } };
                    }
                    
                    // Alinhar Score ao centro
                    if (C === 6) {
                        worksheet[cell_ref].s.alignment.horizontal = 'center';
                    }
                }
            }
            
            // Ajustar largura das colunas
            worksheet['!cols'] = [
                { wch: 30 }, // Programa
                { wch: 30 }, // Área de Concentração
                { wch: 40 }, // Linha de Pesquisa
                { wch: 30 }, // Área de Taxonomia
                { wch: 20 }, // Domínio
                { wch: 40 }, // Subdomínio
                { wch: 10 }  // Score
            ];
            
            // Definir altura das linhas
            worksheet['!rows'] = [{ hpt: 20 }]; // Altura padrão
            worksheet['!rows'][0] = { hpt: 25 }; // Altura do cabeçalho
            
            var workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Resultados");
            
            // Gerar nome do arquivo com data
            var fileName = 'resultados_filtrados_' + new Date().toISOString().slice(0, 10) + '.xlsx';
            
            // Exportar arquivo
            XLSX.writeFile(workbook, fileName);
        }

        // Inicialização
        document.addEventListener("DOMContentLoaded", function() {
            renderChordDiagram('chordDiagram1', 'chordTooltip1', 'programasLegend1', 'segmentosLegend1', chordData1, true);
            renderChordDiagram('chordDiagram2', 'chordTooltip2', 'areasLegend2', 'segmentosLegend2', chordData2, false);
            
            // Adicionar listener para o formulário de filtros
            document.getElementById('filterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                applyFilters();
            });
        });
    </script>
</body>
</html>
