<?php
header('Content-Type: text/html; charset=UTF-8');

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'sc2c';
$username = 'root';
$password = '';

try {
    // Conexão com o banco de dados
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ATTR_ERRMODE_EXCEPTION);
    
    // Consulta para obter os dados para o relatório
    $stmt = $pdo->query("
        SELECT 
            p.nome AS Programa,
            a.nome AS Area,
            lp.nome AS Linha_de_Pesquisa,
            lp.comentario AS Comentario,
            ca.codigo AS ACARE_Codigo,
            ca.descricao AS ACARE_Descricao,
            ca.score AS ACARE_Score,
            ca.aprovado AS ACARE_Aprovado,
            ca.usuario_aprovador AS ACARE_Aprovador,
            ca.data_aprovacao AS ACARE_Data_Aprovacao,
            cn.codigo AS NASA_Codigo,
            cn.descricao AS NASA_Descricao,
            cn.score AS NASA_Score,
            cn.aprovado AS NASA_Aprovado,
            cn.usuario_aprovador AS NASA_Aprovador,
            cn.data_aprovacao AS NASA_Data_Aprovacao,
            f.usuario AS Feedback_Usuario,
            f.comentario AS Feedback_Comentario,
            f.created_at AS Feedback_Data
        FROM linhas_pesquisa lp
        JOIN areas a ON lp.area_id = a.id
        JOIN programas p ON a.programa_id = p.id
        LEFT JOIN classificacoes_acare ca ON ca.linha_pesquisa_id = lp.id
        LEFT JOIN classificacoes_nasa cn ON cn.linha_pesquisa_id = lp.id
        LEFT JOIN feedbacks f ON f.linha_pesquisa_id = lp.id
        ORDER BY p.nome, a.nome, lp.nome
    ");
    
    // Criar arquivo CSV (que pode ser aberto no Excel)
    $filename = 'relatorio_classificacoes_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Cabeçalho do CSV
    fputcsv($output, array(
        'Programa',
        'Área',
        'Linha de Pesquisa',
        'Comentário',
        'Classificação ACARE',
        'Descrição ACARE',
        'Score ACARE',
        'Status Aprovação ACARE',
        'Aprovador ACARE',
        'Data Aprovação ACARE',
        'Classificação NASA',
        'Descrição NASA',
        'Score NASA',
        'Status Aprovação NASA',
        'Aprovador NASA',
        'Data Aprovação NASA',
        'Feedback Usuário',
        'Feedback Comentário',
        'Feedback Data'
    ));
    
    // Dados
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array(
            $row['Programa'],
            $row['Area'],
            $row['Linha_de_Pesquisa'],
            $row['Comentario'],
            $row['ACARE_Codigo'],
            $row['ACARE_Descricao'],
            $row['ACARE_Score'],
            $row['ACARE_Aprovado'] === null ? 'Pendente' : ($row['ACARE_Aprovado'] ? 'Aprovado' : 'Rejeitado'),
            $row['ACARE_Aprovador'],
            $row['ACARE_Data_Aprovacao'],
            $row['NASA_Codigo'],
            $row['NASA_Descricao'],
            $row['NASA_Score'],
            $row['NASA_Aprovado'] === null ? 'Pendente' : ($row['NASA_Aprovado'] ? 'Aprovado' : 'Rejeitado'),
            $row['NASA_Aprovador'],
            $row['NASA_Data_Aprovacao'],
            $row['Feedback_Usuario'],
            $row['Feedback_Comentario'],
            $row['Feedback_Data']
        ));
    }
    
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    die("Erro ao gerar relatório: " . $e->getMessage());
}
?>