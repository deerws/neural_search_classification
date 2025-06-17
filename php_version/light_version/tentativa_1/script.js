// A variável `taxonomies` é carregada a partir do arquivo index.php

// Variáveis de estado globais
var currentRowIndex = null;
var selectedSubdomain = null;
var approvals = {};
var feedbacks = {};

// Função para popular o modal de adição de subdomínio
function populateSubdomainModal(program, containerId) {
    var container = $('#' + containerId);
    container.empty();
    $.each(taxonomies[program], function(domainCode, domainData) {
        var domainPanel = $('<div class="panel panel-default">');
        var panelHeading = $('<div class="panel-heading">').html(
            '<h4 class="panel-title">' +
            '<a data-toggle="collapse" href="#' + program.toLowerCase() + 'Collapse' + domainCode + '">' +
            domainCode + ' - ' + domainData.name +
            '</a></h4>'
        );

        var panelBody = $('<div id="' + program.toLowerCase() + 'Collapse' + domainCode + '" class="panel-collapse collapse">')
            .append($('<div class="panel-body">'));

        if (domainData.subdomains) {
            $.each(domainData.subdomains, function(subdomainCode, subdomainDesc) {
                var fullCode = subdomainCode;
                panelBody.find('.panel-body').append(
                    $('<div class="subdomain-item">').html(
                        '<input type="radio" name="' + program.toLowerCase() + 'Subdomain" value="' + fullCode + '"> ' +
                        '<label for="radio-' + fullCode + '">' + fullCode + ' - ' + subdomainDesc + '</label>'
                    ).click(function() {
                        selectedSubdomain = {
                            program: program,
                            code: fullCode,
                            description: subdomainDesc,
                            score: 1.0
                        };
                    })
                );
            });
        }
        domainPanel.append(panelHeading).append(panelBody);
        container.append(domainPanel);
    });
}


$(document).ready(function() {
    // A função initializeCharts() é chamada diretamente do index.php,
    // logo após a sua definição, para garantir que os dados dinâmicos sejam carregados.
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

    // Ações de aprovar/rejeitar (usando delegação de eventos para funcionar em itens adicionados dinamicamente)
    $('body').on('click', '.btn-approve', function() {
        var userName = $('#userName').val();
        if (!userName) {
            alert('Por favor, digite seu nome antes de aprovar.');
            return;
        }
        var classificationDiv = $(this).closest('.classification');
        classificationDiv.addClass('approved').removeClass('rejected');
        // Lógica de armazenamento do status pode ser adicionada aqui
    });

    $('body').on('click', '.btn-reject', function() {
        var userName = $('#userName').val();
        if (!userName) {
            alert('Por favor, digite seu nome antes de rejeitar.');
            return;
        }
        var classificationDiv = $(this).closest('.classification');
        classificationDiv.addClass('rejected').removeClass('approved');
        // Lógica de armazenamento do status pode ser adicionada aqui
    });

    // Botão de feedback
    $('body').on('click', '.btn-feedback', function() {
        currentRowIndex = $(this).closest('tr').data('row');
        $('#modalRowNumber').text(currentRowIndex + 1);
        // Lógica para carregar e exibir feedbacks existentes
        $('#feedbackModal').modal('show');
    });
    
    // Envio do feedback
    $('#submitFeedback').click(function() {
        // Lógica para salvar o feedback
        $('#feedbackModal').modal('hide');
    });

    // Botão para adicionar subdomínio
    $('body').on('click', '.btn-add-subdomain', function() {
        currentRowIndex = $(this).closest('tr').data('row');
        selectedSubdomain = null;
        populateSubdomainModal('ACARE', 'acareDomains');
        populateSubdomainModal('NASA', 'nasaDomains');
        $('#subdomainModal').modal('show');
    });
    
    // Submissão de novo subdomínio
    $('#submitSubdomain').click(function() {
        if (!selectedSubdomain) {
            alert('Por favor, selecione um subdomínio.');
            return;
        }
        // Lógica para adicionar o novo subdomínio à tabela
        $('#subdomainModal').modal('hide');
    });

    // Botão de download
    $('#downloadBtn').click(function() {
        alert("Funcionalidade de download do CSV a ser implementada.");
    });
});