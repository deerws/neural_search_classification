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
    
    // Verifica se o arquivo existe e é legível
    if (!file_exists($filename) || !is_readable($filename)) {
        trigger_error("Arquivo CSV não encontrado ou não pode ser lido: " . $filename, E_USER_WARNING);
        return $data;
    }
    
    // Lê o conteúdo do arquivo e converte para UTF-8 explicitamente
    $content = file_get_contents($filename);
    $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    
    if ($encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding ?: 'auto');
    }
    
    // Remove BOM se existir
    $bom = pack('H*','EFBBBF');
    $content = preg_replace("/^$bom/", '', $content);
    
    // Escreve o conteúdo convertido em um arquivo temporário
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($tempFile, $content);
    
    if (($handle = fopen($tempFile, "r")) !== FALSE) {
        // Lê o cabeçalho
        if (($header = fgetcsv($handle, 0, ";")) === false) {
            fclose($handle);
            unlink($tempFile);
            return $data;
        }
        
        $header = array_map('trim', $header);
        
        // Lê as linhas de dados
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

// Carregar dados
$csvFile = 'final_aprovações_BACKUP.csv';
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
// Preparar dados para o gráfico de corda
$programRelations = array();
foreach ($data as $row) {
    if (!isset($row['Programa'])) continue;
    
    $program = $row['Programa'];
    $area = $row['Área'] ?? 'N/A';
    
    if (!isset($programRelations[$program])) {
        $programRelations[$program] = array();
    }
    $programRelations[$program][$area] = ($programRelations[$program][$area] ?? 0) + 1;
}

// Converter para formato do chord diagram
$allItems = array_values(array_unique(array_merge(
    array_keys($programRelations),
    array_keys(array_merge(...array_values($programRelations)))
)));

$matrix = array_fill(0, count($allItems), array_fill(0, count($allItems), 0));

foreach ($programRelations as $program => $relations) {
    $i = array_search($program, $allItems);
    foreach ($relations as $area => $count) {
        $j = array_search($area, $allItems);
        $matrix[$i][$j] += $count;
        $matrix[$j][$i] += $count; // Adiciona a relação inversa para simetria
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
        .network-container {
            width: 100%;
            height: 600px;
            margin: 20px 0;
            border: 1px solid #444;
            border-radius: 4px;
            background: #252525;
        }

        .graph-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #e0e0e0;
        }

        .legend-color {
            width: 15px;
            height: 15px;
            margin-right: 5px;
            border-radius: 3px;
        }
        .container {
            max-width: none;
            width: 98%;
            margin: 0 auto;
            padding: 0 10px;
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
                <div id="chordDiagram" class="chord-container"></div>
                <div id="chordTooltip" class="chord-tooltip"></div>
                <div id="chordLegend" class="chord-legend"></div>
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
        function loadCSVData() {
            fetch('get_csv_data.php') // Arquivo PHP que lê o CSV
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        alert("Nenhum dado encontrado no CSV!");
                        return;
                    }

                    const headerRow = document.getElementById('headerRow');
                    const tableBody = document.getElementById('tableBody');
                    const columnSelect = document.getElementById('columnSelect');

                    // Cria cabeçalhos da tabela e opções do filtro
                    Object.keys(data[0]).forEach(key => {
                        const th = document.createElement('th');
                        th.textContent = key;
                        headerRow.appendChild(th);

                        const option = document.createElement('option');
                        option.value = key;
                        option.textContent = key;
                        columnSelect.appendChild(option);
                    });

                    // Preenche as linhas da tabela
                    data.forEach(row => {
                        const tr = document.createElement('tr');
                        Object.values(row).forEach(value => {
                            const td = document.createElement('td');
                            td.textContent = value;
                            tr.appendChild(td);
                        });
                        tableBody.appendChild(tr);
                    });
                })
                .catch(error => console.error('Erro ao carregar CSV:', error));
        }

        // Filtra a tabela com base no input de busca
        function filterTable() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const column = document.getElementById('columnSelect').value;
            const rows = document.getElementById('tableBody').getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    if (column === 'all' || j === Array.from(document.getElementById('headerRow').children).findIndex(th => th.textContent === column)) {
                        if (cells[j].textContent.toLowerCase().includes(input)) {
                            found = true;
                            break;
                        }
                    }
                }

                rows[i].style.display = found ? '' : 'none';
            }
        }

        // Carrega os dados quando a página é aberta
        window.onload = loadCSVData;        
        function applyFilters() {
            const filters = {
                programa: document.getElementById("programa").value,
                area: document.getElementById("area").value,
                status: document.getElementById("status").value,
                classificacao: document.getElementById("classificacao").value
            };
            
            fetch(`get_filtered_data.php?${new URLSearchParams(filters)}`)
                .then(response => response.json())
                .then(data => {
                    // Processa os dados como no PHP
                    const { matrix, labels } = processDataForChord(data);
                    updateChordDiagram({ matrix, labels });
                });
        }        
        // Dados preparados no PHP - precisamos passá-los para o JavaScript
        const chordData = {
            matrix: <?php echo json_encode($matrix); ?>,
            labels: <?php echo json_encode($allItems); ?>
        };

        // Cores para os grupos (você pode personalizar)
        const colorScheme = d3.scaleOrdinal(d3.schemeCategory10);

        // Função para renderizar o chord diagram
        function renderChordDiagram() {
            // Limpa o container antes de renderizar
            d3.select("#chordDiagram").html("");
            
            // Verifica se temos dados
            if (chordData.matrix.length === 0 || chordData.labels.length === 0) {
                console.warn("Sem dados para renderizar o chord diagram");
                return;
            }
            
            // Configurações do gráfico
            const width = document.getElementById("chordDiagram").clientWidth;
            const height = Math.min(width, 600);
            const outerRadius = Math.min(width, height) * 0.5 - 40;
            const innerRadius = outerRadius - 30;
            
            // Cria o layout do chord
            const chord = d3.chord()
                .padAngle(0.05)
                .sortSubgroups(d3.descending);
            
            // Cria o arc generator
            const arc = d3.arc()
                .innerRadius(innerRadius)
                .outerRadius(outerRadius);
            
            // Cria o ribbon generator
            const ribbon = d3.ribbon()
                .radius(innerRadius);
            
            // Cria o SVG
            const svg = d3.select("#chordDiagram").append("svg")
                .attr("width", width)
                .attr("height", height)
                .attr("viewBox", [-width / 2, -height / 2, width, height])
                .attr("style", "max-width: 100%; height: auto;");
            
            // Adiciona um grupo para o gráfico
            const g = svg.append("g");
            
            // Computa os relacionamentos
            const chords = chord(chordData.matrix);
            
            // Adiciona os grupos (arcos externos)
            const group = g.append("g")
                .selectAll("g")
                .data(chords.groups)
                .join("g");
            
            group.append("path")
                .attr("fill", d => colorScheme(d.index))
                .attr("d", arc)
                .on("mouseover", function(d) {
                    // Destaca este grupo e suas conexões
                    d3.select(this).attr("stroke", "#fff").attr("stroke-width", 2);
                    ribbonGroup.filter(dd => dd.source.index === d.index || dd.target.index === d.index)
                        .attr("stroke", "#fff")
                        .attr("stroke-width", 2);
                })
                .on("mouseout", function(d) {
                    d3.select(this).attr("stroke", null);
                    ribbonGroup.attr("stroke", null);
                });
            
            // Adiciona os tiques (rótulos)
            group.append("text")
                .each(d => { d.angle = (d.startAngle + d.endAngle) / 2; })
                .attr("dy", ".35em")
                .attr("transform", d => `
                    rotate(${d.angle * 180 / Math.PI - 90})
                    translate(${outerRadius + 10})
                    ${d.angle > Math.PI ? "rotate(180)" : ""}
                `)
                .attr("text-anchor", d => d.angle > Math.PI ? "end" : null)
                .text(d => chordData.labels[d.index])
                .style("font-size", "10px")
                .style("fill", "#e0e0e0");
            
            // Adiciona as conexões (ribbons)
            const ribbonGroup = g.append("g")
                .attr("fill-opacity", 0.8)
                .selectAll("path")
                .data(chords)
                .join("path")
                .attr("d", ribbon)
                .attr("fill", d => colorScheme(d.source.index))
                .attr("stroke", "#333")
                .on("mouseover", function(d) {
                    // Mostra tooltip com informações
                    const tooltip = d3.select("#chordTooltip");
                    tooltip.style("display", "block")
                        .html(`
                            <strong>${chordData.labels[d.source.index]}</strong> → 
                            <strong>${chordData.labels[d.target.index]}</strong><br>
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
            
            // Cria a legenda
            const legend = d3.select("#chordLegend");
            legend.selectAll(".chord-legend-item")
                .data(chordData.labels)
                .join("div")
                .attr("class", "chord-legend-item")
                .html((d, i) => `
                    <span class="chord-legend-color" style="background:${colorScheme(i)}"></span>
                    ${d}
                `);
        }

        // Renderiza o gráfico quando a página carrega
        document.addEventListener("DOMContentLoaded", renderChordDiagram);

        // Função para atualizar o gráfico com dados filtrados
        function updateChordDiagram(filteredData) {
            // Aqui você precisaria processar os dados filtrados como fez no PHP
            // e então chamar renderChordDiagram() com os novos dados
            console.log("Dados filtrados recebidos:", filteredData);
            // renderChordDiagram(filteredData);
        }
    </script>
</body>
</html> 
