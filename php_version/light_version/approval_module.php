<?php
header('Content-Type: text/html; charset=UTF-8');

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

// Carrega os dados do CSV
$csvData = loadCSVData('resultados_formatado.csv');

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

// Processa os dados para ter sempre 3 classificações para ACARE e NASA
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
    
    // Garante pelo menos uma entrada "N/A" se não houver classificações
    if (empty($acare)) {
        $acare[] = array('code' => 'N/A', 'description' => 'Sem classificação', 'score' => 0);
    }
    
    // Ordena por score (maior primeiro)
    usort($acare, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
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
    
    // Garante pelo menos uma entrada "N/A" se não houver classificações
    if (empty($nasa)) {
        $nasa[] = array('code' => 'N/A', 'description' => 'Sem classificação', 'score' => 0);
    }
    
    // Ordena por score (maior primeiro)
    usort($nasa, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    // Adiciona ao array processado
    $processedData[] = array(
        'Programa' => $row['Programa'] ?? '',
        'Área' => $row['Área'] ?? '',
        'Linha_de_Pesquisa' => $row['Linha de Pesquisa'] ?? '',
        'ACARE' => $acare,
        'NASA' => $nasa,
        'Comentário' => $row['Comentário'] ?? ''
    );
}

// Agrupa por programa para o dropdown
$programas = array();
foreach ($processedData as $row) {
    $programa = $row['Programa'];
    if (!in_array($programa, $programas)) {
        $programas[] = $programa;
    }
}
sort($programas);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SC2C.Aero - Módulo de Aprovação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .panel {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #337ab7;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .classification {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        .approved {
            background-color: #dff0d8;
        }
        .rejected {
            background-color: #f2dede;
        }
        .btn-approve {
            background-color: #5cb85c;
            color: white;
        }
        .btn-reject {
            background-color: #d9534f;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="panel">
            <h1 class="text-center">SC2C.Aero - Módulo de Aprovação</h1>
            
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
            
            <div class="text-center" style="margin: 20px 0;">
                <button id="downloadBtn" class="btn btn-primary">Baixar CSV Atualizado</button>
                <a href="https://www.daccampania.com/wp-content/uploads/2022/01/ACARE_Taxonomy.pdf" 
                   target="_blank" class="btn btn-info">Manual ACARE</a>
                <a href="https://www3.nasa.gov/sites/default/files/atoms/files/2020_nasa_technology_taxonomy.pdf" 
                   target="_blank" class="btn btn-info">Manual NASA</a>
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
                                <li>Selecione um programa para filtrar (opcional)</li>
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
                                <li>Aprove ou rejeite cada subdomínio</li>
                                <li>Adicione novos subdomínios se necessário</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">3. Finalização</div>
                        <div class="panel-body">
                            <ul>
                                <li>Adicione comentários se necessário</li>
                                <li>Baixe o CSV com seus resultados</li>
                                <li>Consulte os manuais para referência</li>
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
                        <tr data-row="<?php echo $index; ?>">
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
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <button class="btn btn-xs btn-default btn-feedback">Feedback</button>
                                <button class="btn btn-xs btn-primary btn-add-subdomain">Adicionar Subdomínio</button>
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

    <!-- Modal de Subdomínio -->
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
                            <div class="panel-group" id="acareDomains">
                                <!-- Domínios ACARE serão preenchidos via JavaScript -->
                            </div>
                            <button class="btn btn-link" id="addCustomAcare">➕ Criar Subdomínio Personalizado</button>
                        </div>
                        <div class="tab-pane" id="nasaTab">
                            <div class="panel-group" id="nasaDomains">
                                <!-- Domínios NASA serão preenchidos via JavaScript -->
                            </div>
                            <button class="btn btn-link" id="addCustomNasa">➕ Criar Subdomínio Personalizado</button>
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
    <script>
        // Estrutura hierárquica de domínios/subdomínios
        var subdomains = {
            ACARE: {
                '96': {
                    name: 'Aerodinâmica e Física de Voo',
                    subdomains: [
                        { code: '96.1', description: 'Desempenho Aerodinâmico' },
                        { code: '96.2', description: 'Dinâmica de Voo' },
                        { code: '96.3', description: 'Controle de Fluxo' }
                    ]
                },
                '97': {
                    name: 'Estruturas de Aeronaves',
                    subdomains: [
                        { code: '97.1', description: 'Projeto Estrutural' },
                        { code: '97.2', description: 'Tecnologia de Materiais' },
                        { code: '97.3', description: 'Manufatura Avançada' }
                    ]
                }
            },
            NASA: {
                'A1': {
                    name: 'Pesquisa Aeronáutica',
                    subdomains: [
                        { code: 'A1.1', description: 'Veículos Aéreos Avançados' },
                        { code: 'A1.2', description: 'Operações no Espaço Aéreo' },
                        { code: 'A1.3', description: 'Sistemas de Propulsão' }
                    ]
                },
                'A2': {
                    name: 'Tecnologia Espacial',
                    subdomains: [
                        { code: 'A2.1', description: 'Sistemas Autônomos' },
                        { code: 'A2.2', description: 'Voo Espacial Humano' },
                        { code: 'A2.3', description: 'Materiais Aeroespaciais' }
                    ]
                }
            }
        };
        
        // Variáveis de estado
        var currentRowIndex = null;
        var currentProgram = 'ACARE';
        var selectedSubdomain = null;
        var approvals = {};
        var feedbacks = {};
        
        // Inicializa a interface
        $(document).ready(function() {
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
            
            // Atribui o programa a cada linha
            <?php foreach ($processedData as $index => $row): ?>
                $('tr[data-row="<?php echo $index; ?>"]').data('program', '<?php echo htmlspecialchars($row['Programa']); ?>');
            <?php endforeach; ?>
            
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
            });
            
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
                    var domainPanel = $('<div class="panel panel-default">');
                    var panelHeading = $('<div class="panel-heading">').html(
                        '<h4 class="panel-title">' + 
                        '<a data-toggle="collapse" href="#acareCollapse' + domainCode + '">' +
                        domainCode + ' - ' + domainData.name +
                        '</a></h4>'
                    );
                    
                    var panelBody = $('<div id="acareCollapse' + domainCode + '" class="panel-collapse collapse">')
                        .append($('<div class="panel-body">'));
                    
                    $.each(domainData.subdomains, function(i, subdomain) {
                        panelBody.find('.panel-body').append(
                            $('<div class="subdomain-item">').html(
                                '<input type="radio" name="acareSubdomain" value="' + subdomain.code + '" id="acare-' + subdomain.code + '"> ' +
                                '<label for="acare-' + subdomain.code + '">' + subdomain.code + ' - ' + subdomain.description + '</label>'
                            ).click(function() {
                                selectedSubdomain = 'ACARE:' + subdomain.code;
                            })
                        );
                    });
                    
                    domainPanel.append(panelHeading).append(panelBody);
                    $('#acareDomains').append(domainPanel);
                });
                
                // Preenche os domínios NASA
                $('#nasaDomains').empty();
                $.each(subdomains.NASA, function(domainCode, domainData) {
                    var domainPanel = $('<div class="panel panel-default">');
                    var panelHeading = $('<div class="panel-heading">').html(
                        '<h4 class="panel-title">' + 
                        '<a data-toggle="collapse" href="#nasaCollapse' + domainCode + '">' +
                        domainCode + ' - ' + domainData.name +
                        '</a></h4>'
                    );
                    
                    var panelBody = $('<div id="nasaCollapse' + domainCode + '" class="panel-collapse collapse">')
                        .append($('<div class="panel-body">'));
                    
                    $.each(domainData.subdomains, function(i, subdomain) {
                        panelBody.find('.panel-body').append(
                            $('<div class="subdomain-item">').html(
                                '<input type="radio" name="nasaSubdomain" value="' + subdomain.code + '" id="nasa-' + subdomain.code + '"> ' +
                                '<label for="nasa-' + subdomain.code + '">' + subdomain.code + ' - ' + subdomain.description + '</label>'
                            ).click(function() {
                                selectedSubdomain = 'NASA:' + subdomain.code;
                            })
                        );
                    });
                    
                    domainPanel.append(panelHeading).append(panelBody);
                    $('#nasaDomains').append(domainPanel);
                });
                
                $('#subdomainModal').modal('show');
            });
            
            // Criar subdomínio personalizado ACARE
            $('#addCustomAcare').click(function() {
                var code = prompt('Digite o código do subdomínio personalizado (ex: 96.4):');
                if (code) {
                    var description = prompt('Digite a descrição:');
                    if (description) {
                        selectedSubdomain = 'ACARE:custom:' + code + '.' + description;
                    }
                }
            });
            
            // Criar subdomínio personalizado NASA
            $('#addCustomNasa').click(function() {
                var code = prompt('Digite o código do subdomínio personalizado (ex: A1.4):');
                if (code) {
                    var description = prompt('Digite a descrição:');
                    if (description) {
                        selectedSubdomain = 'NASA:custom:' + code + '.' + description;
                    }
                }
            });
            
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
                    code = customParts[0];
                    description = customParts.slice(1).join('.');
                } else {
                    // Encontra a descrição do subdomínio padrão
                    var found = false;
                    $.each(subdomains[program], function(domainCode, domainData) {
                        $.each(domainData.subdomains, function(i, subdomain) {
                            if (subdomain.code === type) {
                                code = subdomain.code;
                                description = subdomain.description;
                                found = true;
                                return false;
                            }
                        });
                        if (found) return false;
                    });
                    
                    if (!found) {
                        code = type;
                        description = 'Subdomínio personalizado';
                    }
                }
                
                // Adiciona à tabela
                var newClassification = {
                    code: code,
                    description: description,
                    score: 0.0
                };
                
                var row = $('tr[data-row="' + currentRowIndex + '"]');
                var cell = program === 'ACARE' ? row.find('td:nth-child(3)') : row.find('td:nth-child(4)');
                
                // Verifica se já existe uma classificação "N/A" para substituir
                var naDiv = cell.find('.classification:contains("N/A: Sem classificação")');
                if (naDiv.length > 0) {
                    naDiv.replaceWith(createClassificationDiv(newClassification, program, cell.find('.classification').length));
                } else {
                    cell.append(createClassificationDiv(newClassification, program, cell.find('.classification').length));
                }
                
                $('#subdomainModal').modal('hide');
            });
            
            // Botão para baixar CSV
            $('#downloadBtn').click(function() {
                var userName = $('#userName').val().trim();
                var cleanedUserName = userName.replace(/[^a-zA-Z0-9]/g, '_') || 'classification';
                
                // Cria o conteúdo CSV
                var csvContent = "Programa,Área,Linha de Pesquisa,Usuário,Classificação Aprovada,Data de Aprovação,Feedback,ACARE 1,ACARE 2,ACARE 3,NASA 1,NASA 2,NASA 3\n";
                
                $('tbody tr').each(function() {
                    var rowIndex = $(this).data('row');
                    var rowData = <?php echo json_encode($processedData); ?>[rowIndex];
                    
                    // Verifica se há aprovações para esta linha
                    var hasApprovals = false;
                    for (var key in approvals) {
                        if (key.startsWith(rowIndex + '-')) {
                            hasApprovals = true;
                            break;
                        }
                    }
                    
                    if (hasApprovals) {
                        // Coleta todas as aprovações para esta linha
                        var lineApprovals = [];
                        for (var i = 0; i < 3; i++) {
                            var acareKey = rowIndex + '-acare-' + i;
                            if (approvals[acareKey]) {
                                lineApprovals.push('ACARE ' + (i+1) + ': ' + 
                                    rowData.ACARE[i].code + ' - ' + rowData.ACARE[i].description + 
                                    ' (' + rowData.ACARE[i].score.toFixed(3) + ') - ' + approvals[acareKey]);
                            }
                        }
                        for (var i = 0; i < 3; i++) {
                            var nasaKey = rowIndex + '-nasa-' + i;
                            if (approvals[nasaKey]) {
                                lineApprovals.push('NASA ' + (i+1) + ': ' + 
                                    rowData.NASA[i].code + ' - ' + rowData.NASA[i].description + 
                                    ' (' + rowData.NASA[i].score.toFixed(3) + ') - ' + approvals[nasaKey]);
                            }
                        }
                        
                        // Adiciona ao CSV
                        csvContent += '"' + rowData.Programa.replace(/"/g, '""') + '",';
                        csvContent += '"' + rowData.Área.replace(/"/g, '""') + '",';
                        csvContent += '"' + rowData.Linha_de_Pesquisa.replace(/"/g, '""') + '",';
                        csvContent += '"' + userName.replace(/"/g, '""') + '",';
                        csvContent += '"' + lineApprovals.join('; ').replace(/"/g, '""') + '",';
                        csvContent += '"' + new Date().toISOString().replace('T', ' ').split('.')[0] + '",';
                        csvContent += '"' + (feedbacks[rowIndex] ? feedbacks[rowIndex].join('; ').replace(/"/g, '""') : '') + '",';
                        
                        // Adiciona as classificações ACARE e NASA
                        for (var i = 0; i < 3; i++) {
                            if (rowData.ACARE[i] && rowData.ACARE[i].code !== 'N/A') {
                                csvContent += '"' + rowData.ACARE[i].code + ': ' + rowData.ACARE[i].description + 
                                    ' (' + rowData.ACARE[i].score.toFixed(3) + ')",';
                            } else {
                                csvContent += '"",';
                            }
                        }
                        for (var i = 0; i < 3; i++) {
                            if (rowData.NASA[i] && rowData.NASA[i].code !== 'N/A') {
                                csvContent += '"' + rowData.NASA[i].code + ': ' + rowData.NASA[i].description + 
                                    ' (' + rowData.NASA[i].score.toFixed(3) + ')",';
                            } else {
                                csvContent += '"",';
                            }
                        }
                        
                        csvContent = csvContent.slice(0, -1) + "\n"; // Remove a última vírgula e adiciona nova linha
                    }
                });
                
                // Cria e dispara o download
                var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = cleanedUserName + '_classification_approval.csv';
                link.click();
                URL.revokeObjectURL(url);
            });
        });
        
        // Função auxiliar para criar div de classificação
        function createClassificationDiv(classification, type, index) {
            return $('<div class="classification" data-type="' + type.toLowerCase() + '" data-index="' + index + '">').html(
                '<strong>' + classification.code + ':</strong> ' + 
                classification.description + ' (' + classification.score.toFixed(3) + ')' +
                '<div class="actions" style="margin-top: 5px;">' +
                '<button class="btn btn-xs btn-approve">Aprovar</button> ' +
                '<button class="btn btn-xs btn-reject">Rejeitar</button>' +
                '</div>'
            );
        }
    </script>
</body>
</html>
