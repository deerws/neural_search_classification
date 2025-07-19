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
    if (($handle = fopen($filename, "r")) !== FALSE) {
        // Ler cabeçalho
        $header = fgetcsv($handle, 0, ";");
        
        // Verificar encoding e converter se necessário
        if (!mb_check_encoding(implode('', $header), 'UTF-8')) {
            $header = array_map(function($item) {
                return mb_convert_encoding($item, 'UTF-8', 'auto');
            }, $header);
        }
        
        while (($row = fgetcsv($handle, 0, ";")) !== FALSE) {
            // Converter encoding se necessário
            if (!mb_check_encoding(implode('', $row), 'UTF-8')) {
                $row = array_map(function($item) {
                    return mb_convert_encoding($item, 'UTF-8', 'auto');
                }, $row);
            }
            
            // Combinar apenas se tiver o mesmo número de colunas
            if (count($header) == count($row)) {
                $data[] = array_combine($header, $row);
            }
        }
        fclose($handle);
    }
    return $data;
}

// Carregar dados
$csvFile = 'Book11111111.csv';
$data = file_exists($csvFile) ? readCSV($csvFile) : array();

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

// Aplicar filtros
$filters = array(
    'search' => isset($_GET['search']) ? $_GET['search'] : '',
    'programa' => isset($_GET['programa']) ? $_GET['programa'] : '',
    'area' => isset($_GET['area']) ? $_GET['area'] : '',
    'status' => isset($_GET['status']) ? $_GET['status'] : '',
    'classificacao' => isset($_GET['classificacao']) ? $_GET['classificacao'] : ''
);

$filteredData = $data;
foreach ($filters as $field => $value) {
    if (!empty($value)) {
        if ($field == 'search') {
            $filteredData = filterByTerm($filteredData, $value);
        } else {
            $fieldName = ($field == 'classificacao') ? 'Classificação' : ucfirst($field);
            $filteredData = filterByField($filteredData, $fieldName, $value);
        }
    }
}

// Preparar dados para visualização
$stats = array(
    'total' => count($data),
    'aprovados' => 0,
    'rejeitados' => 0,
    'em_analise' => 0
);

foreach ($data as $row) {
    if (isset($row['Status'])) {
        switch ($row['Status']) {
            case 'Aprovado': $stats['aprovados']++; break;
            case 'Rejeitado': $stats['rejeitados']++; break;
            case 'Em Análise': $stats['em_analise']++; break;
        }
    }
}

// Preparar dados para o gráfico de corda
$chordData = array();
$programRelations = array();

foreach ($data as $row) {
    if (!isset($row['Programa']) || !isset($row['Área'])) continue;
    
    $program = $row['Programa'];
    $area = $row['Área'];
    
    if (!isset($programRelations[$program])) {
        $programRelations[$program] = array();
    }
    
    if (!isset($programRelations[$program][$area])) {
        $programRelations[$program][$area] = 0;
    }
    
    $programRelations[$program][$area]++;
}

// Converter para formato adequado para o gráfico de corda
$programs = array_keys($programRelations);
$areas = array();
foreach ($programRelations as $rel) {
    $areas = array_merge($areas, array_keys($rel));
}
$areas = array_unique($areas);

$matrix = array();
foreach ($programs as $i => $program) {
    $matrix[$i] = array();
    foreach ($areas as $j => $area) {
        $matrix[$i][$j] = isset($programRelations[$program][$area]) ? $programRelations[$program][$area] : 0;
    }
}

// Obter valores únicos para filtros
$programas = array();
$areasFiltro = array();
$statusList = array();
$classificacoes = array();

foreach ($data as $row) {
    if (isset($row['Programa']) && !in_array($row['Programa'], $programas)) {
        $programas[] = $row['Programa'];
    }
    if (isset($row['Área']) && !in_array($row['Área'], $areasFiltro)) {
        $areasFiltro[] = $row['Área'];
    }
    if (isset($row['Status']) && !in_array($row['Status'], $statusList)) {
        $statusList[] = $row['Status'];
    }
    if (isset($row['Classificação']) && !in_array($row['Classificação'], $classificacoes)) {
        $classificacoes[] = $row['Classificação'];
    }
}

sort($programas);
sort($areasFiltro);
sort($statusList);
sort($classificacoes);
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
    <style>
        /* Tema Escuro Uniforme - SC2C.Aero */
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            padding: 20px;
        }
        
        .header-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .logo-title-container {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
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
        
        .container {
            max-width: none;
            width: 98%;
            margin: 0 auto;
            padding: 0 10px;
        }
                .chord-container {
            width: 100%;
            height: 600px;
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
        }
        
        button {
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
        }
        
        .table-container {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 75vh;
            width: 100%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #252525;
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
            width: 100px;
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
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-info {
            background-color: #17a2b8;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background-color: #d32f2f;
        }
        
        .download-buttons, .manual-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin: 15px 0;
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
                    <h2 class="subtitle">Taxonomia de Pesquisa - Consulta</h2>
                </div>
            </div>
        </div>
        
        <div class="panel">
            <div class="filter-section">
                <h2>Filtros</h2>
                <form method="GET">
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
                            <label for="classificacao">Classificação</label>
                            <select id="classificacao" name="classificacao">
                                <option value="">Todas</option>
                                <?php foreach ($classificacoes as $class): ?>
                                    <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $filters['classificacao'] == $class ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group" style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                            <button type="button" class="btn btn-default" onclick="window.location.href='?'">Limpar</button>
                        </div>
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
                <h2>Relação Programas x Áreas</h2>
                <div class="chord-container" id="chordDiagram"></div>
                <div class="chord-tooltip" id="chordTooltip"></div>
                <div class="chord-legend" id="chordLegend"></div>
            </div>
            
            
            <h2>Resultados (<?php echo count($filteredData); ?> registros)</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Programa</th>
                            <th>Área</th>
                            <th>Linha de Pesquisa</th>
                            <th>Usuário</th>
                            <th>Domínio</th>
                            <th>Subdomínio</th>
                            <th>Classificação</th>
                            <th>Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredData as $row): ?>
                            <tr>
                                <td><?php echo isset($row['Programa']) ? htmlspecialchars($row['Programa']) : ''; ?></td>
                                <td><?php echo isset($row['Área']) ? htmlspecialchars($row['Área']) : ''; ?></td>
                                <td><?php echo isset($row['Linha de Pesquisa']) ? htmlspecialchars($row['Linha de Pesquisa']) : ''; ?></td>
                                <td><?php echo isset($row['Usuário']) ? htmlspecialchars($row['Usuário']) : ''; ?></td>
                                <td><?php echo isset($row['Domínio']) ? htmlspecialchars($row['Domínio']) : ''; ?></td>
                                <td><?php echo isset($row['Subdomínio']) ? htmlspecialchars($row['Subdomínio']) : ''; ?></td>
                                <td><?php echo isset($row['Classificação']) ? htmlspecialchars($row['Classificação']) : ''; ?></td>
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
                                <td colspan="9" style="text-align: center;">Nenhum resultado encontrado com os filtros aplicados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Gráfico de Corda Interativo
        function renderChordDiagram(data) {
            const width = 800;
            const height = 800;
            const innerRadius = Math.min(width, height) * 0.4;
            const outerRadius = innerRadius * 1.1;
            
            // Limpar o container
            d3.select("#chordDiagram").html("");
            d3.select("#chordLegend").html("");
            
            // Verificar se há dados para mostrar
            if (data.programs.length === 0 || data.areas.length === 0) {
                d3.select("#chordDiagram").html("<p>Não há dados suficientes para exibir o gráfico</p>");
                return;
            }
            
            // Criar escalas de cores
            const programColors = d3.scaleOrdinal()
                .domain(data.programs)
                .range(d3.quantize(d3.interpolateRainbow, data.programs.length));
            
            const areaColors = d3.scaleOrdinal()
                .domain(data.areas)
                .range(d3.quantize(d3.interpolateCool, data.areas.length));
            
            // Criar o layout do chord
            const chord = d3.chord()
                .padAngle(0.05)
                .sortSubgroups(d3.descending)
                .sortChords(d3.descending);
            
            const chords = chord(data.matrix);
            
            // Criar o SVG
            const svg = d3.select("#chordDiagram")
                .append("svg")
                .attr("width", width)
                .attr("height", height)
                .attr("viewBox", [-width / 2, -height / 2, width, height]);
            
            // Adicionar grupos
            const group = svg.append("g")
                .selectAll("g")
                .data(chords.groups)
                .join("g");
            
            // Adicionar arcos
            group.append("path")
                .attr("fill", d => programColors(data.programs[d.index]))
                .attr("stroke", "#444")
                .attr("d", d3.arc()
                    .innerRadius(innerRadius)
                    .outerRadius(outerRadius)
                )
                .on("mouseover", function(d) {
                    d3.select(this).attr("stroke", "#fff");
                    showTooltip(d, data.programs[d.index], true);
                })
                .on("mouseout", function(d) {
                    d3.select(this).attr("stroke", "#444");
                    hideTooltip();
                });
            
            // Adicionar ticks
            group.append("g")
                .selectAll("g")
                .data(d => d.angles)
                .join("g")
                .attr("transform", d => `
                    rotate(${(d.angle * 180 / Math.PI - 90)})
                    translate(${outerRadius},0)
                `)
                .call(g => g.append("line")
                    .attr("stroke", "#999")
                    .attr("x2", 6)
                )
                .call(g => g.append("text")
                    .attr("x", 8)
                    .attr("dy", "0.35em")
                    .attr("transform", d => d.angle > Math.PI ? "rotate(180) translate(-16)" : null)
                    .attr("text-anchor", d => d.angle > Math.PI ? "end" : null)
                    .text(d => data.areas[d.index])
                    .style("font-size", "10px")
                    .style("fill", "#e0e0e0")
                );
            
            // Adicionar cordas
            svg.append("g")
                .attr("fill-opacity", 0.8)
                .selectAll("path")
                .data(chords)
                .join("path")
                .attr("class", "chord-path")
                .attr("d", d3.ribbon()
                    .radius(innerRadius - 1)
                )
                .attr("fill", d => programColors(data.programs[d.source.index]))
                .attr("stroke", "#333")
                .on("mouseover", function(d) {
                    d3.select(this).attr("stroke", "#fff");
                    showTooltip(d, 
                        `${data.programs[d.source.index]} → ${data.areas[d.target.index]}: ${d.source.value}`, 
                        false
                    );
                })
                .on("mouseout", function(d) {
                    d3.select(this).attr("stroke", "#333");
                    hideTooltip();
                });
            
            // Adicionar legenda
            const legend = d3.select("#chordLegend");
            
            // Legenda para programas
            data.programs.forEach((program, i) => {
                legend.append("div")
                    .attr("class", "chord-legend-item")
                    .html(`
                        <div class="chord-legend-color" style="background:${programColors(program)}"></div>
                        ${program}
                    `);
            });
            
            // Funções do tooltip
            function showTooltip(d, content, isGroup) {
                const tooltip = d3.select("#chordTooltip");
                tooltip.html(content)
                    .style("display", "block")
                    .style("left", (d3.event.pageX + 10) + "px")
                    .style("top", (d3.event.pageY - 28) + "px");
            }
            
            function hideTooltip() {
                d3.select("#chordTooltip").style("display", "none");
            }
        }
        
        // Função para filtrar dados do chord
        function filterChordData() {
            const programa = $("#programa").val();
            const area = $("#area").val();
            const status = $("#status").val();
            const classificacao = $("#classificacao").val();
            
            // Aqui você faria uma requisição AJAX para obter os dados filtrados
            // Estou simulando com os dados existentes para o exemplo
            const filteredData = {
                programs: <?php echo json_encode($programs); ?>,
                areas: <?php echo json_encode($areas); ?>,
                matrix: <?php echo json_encode($matrix); ?>
            };
            
            // Aplicar filtros (simulado)
            if (programa) {
                // Filtrar para mostrar apenas o programa selecionado
                const programIndex = filteredData.programs.indexOf(programa);
                if (programIndex >= 0) {
                    filteredData.matrix = [filteredData.matrix[programIndex]];
                    filteredData.programs = [programa];
                }
            }
            
            renderChordDiagram(filteredData);
        }
        
        // Inicializar o gráfico
        $(document).ready(function() {
            // Renderizar gráfico inicial com todos os dados
            renderChordDiagram({
                programs: <?php echo json_encode($programs); ?>,
                areas: <?php echo json_encode($areas); ?>,
                matrix: <?php echo json_encode($matrix); ?>
            });
            
            // Atualizar gráfico quando filtros mudarem
            $("select").change(filterChordData);
            $("#search").keyup(filterChordData);
            
            // Gráfico de Status
            var statusCtx = document.getElementById('statusChart').getContext('2d');
            var statusChart = new Chart(statusCtx).Doughnut([
                {
                    value: <?php echo $stats['aprovados']; ?>,
                    color: "#4CAF50",
                    highlight: "#66BB6A",
                    label: "Aprovados"
                },
                {
                    value: <?php echo $stats['rejeitados']; ?>,
                    color: "#f44336",
                    highlight: "#ef5350",
                    label: "Rejeitados"
                },
                {
                    value: <?php echo $stats['em_analise']; ?>,
                    color: "#2196F3",
                    highlight: "#42A5F5",
                    label: "Em Análise"
                }
            ], {
                responsive: true,
                animationSteps: 50,
                tooltipTemplate: "<%= label %>: <%= value %> (<%= Math.round(circumference / 6.283 * 100) %>%)"
            });
        });
    </script>
</body>
</html>