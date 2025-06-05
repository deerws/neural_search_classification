<?php
// Enable error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('loginCheck.php');
include('DBconnect.php');

$results = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitClassification'])) {
    $researchName = mysqli_real_escape_string($link, $_POST['researchName']);
    $description = mysqli_real_escape_string($link, $_POST['description'] ?? '');
    
    // Combine inputs for Python script
    $inputText = $researchName;
    if (!empty($description)) {
        $inputText .= "\n" . $description;
    }
    
    // Escape input for command line
    $inputText = escapeshellarg($inputText);
    
    // Execute Python script
    $pythonScript = 'C:\\xampp\\htdocs\\projectDatabase\\classify_taxonomy.py';
    $command = '"C:\\Users\\Admin\\AppData\\Local\\Programs\\Python\\Python313\\python.exe" ' . $pythonScript . ' ' . $inputText . ' 2>&1';
    $output = shell_exec($command);
    
    file_put_contents('C:\\xampp\\htdocs\\projectDatabase\\python_output.log', "Comando: $command\nSaída: $output\n", FILE_APPEND);
    
    if ($output === null || trim($output) === '') {
        $errors[] = "Nenhuma saída do script Python. Comando executado: " . htmlspecialchars($command);
    } else {
        $jsonOutput = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = "Erro ao decodificar JSON: " . json_last_error_msg() . "<br>Saída bruta: " . htmlspecialchars($output);
        } elseif (!isset($jsonOutput['acare']) || !isset($jsonOutput['nasa'])) {
            $errors[] = "Formato JSON inválido. Esperado 'acare' e 'nasa' na saída.<br>Saída bruta: " . htmlspecialchars($output);
        } else {
            $results['acare'] = $jsonOutput['acare'];
            $results['nasa'] = $jsonOutput['nasa'];
            if ($jsonOutput['acare'][0]['similarity'] < 0.75) {
                $results['acare_warning'] = "⚠️ Similaridade baixa — possível erro de classificação (ACARE).";
            }
            if ($jsonOutput['nasa'][0]['similarity'] < 0.75) {
                $results['nasa_warning'] = "⚠️ Similaridade baixa — possível erro de classificação (NASA).";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classificação de Taxonomia | SC2C</title>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="css/general_template.css">
    <link rel="stylesheet" href="css/form_template.css">
</head>

<body>
    <header class='header_nav'>
        <?php include('nav.php') ?>
    </header>

    <div class="container">
        <!-- Classification Form -->
        <div class="box">
            <div class="legend-container">
                <div class="name-box"><b>Classificar Taxonomia de Pesquisa</b></div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    ❌ <?php echo implode('<br>', $errors); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="classifyForm">
                <div class="form-group">
                    <label for="researchName">Nome da Pesquisa:</label>
                    <input type="text" name="researchName" id="researchName" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Descrição (opcional, mas recomendado para melhores resultados):</label>
                    <textarea name="description" id="description" rows="5" placeholder="Descreva detalhadamente o projeto, incluindo objetivos e tecnologias envolvidas."></textarea>
                </div>
                
                <button type="submit" name="submitClassification" class="submit-c">Classificar</button>
            </form>
        </div>

        <!-- Results Section -->
        <?php if (!empty($results)): ?>
            <!-- ACARE Results -->
            <div class="box">
                <div class="legend-container">
                    <div class="name-box"><b>Sugestões de Taxonomia ACARE</b></div>
                </div>
                
                <?php if (isset($results['acare_warning'])): ?>
                    <div class="error-message">
                        <?php echo $results['acare_warning']; ?>
                    </div>
                <?php endif; ?>
                
                <?php foreach ($results['acare'] as $index => $result): ?>
                    <div class="demo-item">
                        <div class="label-box">Sugestão #<?php echo $index + 1; ?></div>
                        <div class="content-container">
                            <div class="info-group">
                                <label>Domínio:</label>
                                <div class='readonly-field'><?php echo htmlspecialchars($result['domain']); ?></div>
                            </div>
                            <div class="info-group">
                                <label>Subdomínio:</label>
                                <div class='readonly-field'><?php echo htmlspecialchars($result['subdomain']); ?></div>
                            </div>
                            <div class="info-group">
                                <label>Similaridade:</label>
                                <div class='readonly-field'><?php echo number_format($result['similarity'], 3); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- NASA Results -->
            <div class="box">
                <div class="legend-container">
                    <div class="name-box"><b>Sugestões de Taxonomia NASA</b></div>
                </div>
                
                <?php if (isset($results['nasa_warning'])): ?>
                    <div class="error-message">
                        <?php echo $results['nasa_warning']; ?>
                    </div>
                <?php endif; ?>
                
                <?php foreach ($results['nasa'] as $index => $result): ?>
                    <div class="demo-item">
                        <div class="label-box">Sugestão #<?php echo $index + 1; ?></div>
                        <div class="content-container">
                            <div class="info-group">
                                <label>Domínio:</label>
                                <div class='readonly-field'><?php echo htmlspecialchars($result['domain']); ?></div>
                            </div>
                            <div class="info-group">
                                <label>Subdomínio:</label>
                                <div class='readonly-field'><?php echo htmlspecialchars($result['subdomain']); ?></div>
                            </div>
                            <div class="info-group">
                                <label>Similaridade:</label>
                                <div class='readonly-field'><?php echo number_format($result['similarity'], 3); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include('footer.php') ?>
</body>
</html>