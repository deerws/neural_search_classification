<?php
// Enable error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('loginCheck.php');
require('DBconnect.php');

if ($tipoUsuario != 'usuario') {
    echo header('Location: login.php');
    exit();
}

$results = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitClassification'])) {
    $researchName = mysqli_real_escape_string($link, $_POST['researchName']);
    $description = mysqli_real_escape_string($link, $_POST['description'] ?? '');
    
    // Combine inputs for Python script
    $inputText = $researchName;
    if (!empty($description)) {
        $inputText .= PHP_EOL . $description;
    }
    
    // Escape input for command execution
    $inputText = escapeshellarg($inputText);
    
    // Check cache
    $cacheDir = 'C:\\xampp\\htdocs\\projectDatabase\\cache\\';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }
    $cacheFile = $cacheDir . md5($inputText) . '.json';
    
    if (file_exists($cacheFile)) {
        $output = file_get_contents($cacheFile);
    } else {
        // Execute Python script
        $pythonScript = 'C:\\xampp\\htdocs\\projectDatabase\\classify_taxonomy.py';
        $command = '"C:\\Users\\Admin\\AppData\\Local\\Programs\\Python\\Python313\\python.exe" ' . $pythonScript . ' ' . $inputText . ' 2>&1';
        $output = shell_exec($command);
        
        // Log command and output for debugging
        file_put_contents('C:\\xampp\\htdocs\\projectDatabase\\python_output.log', "Command: $command\nOutput: $output\n", FILE_APPEND);
        
        // Save to cache if valid JSON
        if ($output && json_decode($output, true)) {
            file_put_contents($cacheFile, $output);
        }
    }
    
    if ($output === null || trim($output) === '') {
        $errors[] = "No output from Python script. Command executed: " . htmlspecialchars($command);
    } else {
        $jsonOutput = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = "Error decoding JSON: " . json_last_error_msg() . "<br>Raw output: " . htmlspecialchars($output);
        } elseif (!isset($jsonOutput['acare']) || !isset($jsonOutput['nasa'])) {
            $errors[] = "Invalid JSON format. Expected 'acare' and 'nasa' in output.<br>Raw output: " . htmlspecialchars($output);
        } else {
            $results['acare'] = $jsonOutput['acare'];
            $results['nasa'] = $jsonOutput['nasa'];
        }
    }
    
    // Redirect to clear POST data and prevent resubmission on refresh
    if (empty($errors)) {
        $_SESSION['results'] = $results;
        header("Location: classifyTaxonomy.php");
        exit();
    }
}

// Load results from session if available
if (isset($_SESSION['results'])) {
    $results = $_SESSION['results'];
    unset($_SESSION['results']); // Clear session after loading
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxonomy Classification | SC2C</title>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="css/general_template.css">
    <link rel="stylesheet" href="css/taxonomy_styles.css">
</head>

<body>
    <header class='header_nav'>
        <?php include('nav.php') ?>
    </header>

    <div class="container">
        <!-- Classification Form -->
        <div class="box form-box">
            <div class="legend-container">
                <div class="name-box"><b>Classify Research Taxonomy</b></div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    ❌ <?php echo implode('<br>', $errors); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="classifyForm" autocomplete="off">
                <div class="form-group">
                    <label for="researchName">Research Name:</label>
                    <input type="text" name="researchName" id="researchName" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description (optional, but recommended for better results):</label>
                    <textarea name="description" id="description" rows="5" placeholder="Provide a detailed description of the project, including objectives and technologies involved."></textarea>
                </div>
                
                <button type="submit" name="submitClassification" class="submit-c">Classify</button>
                <div class="manual-buttons">
                    <a href="https://www.daccampania.com/wp-content/uploads/2022/01/ACARE_Taxonomy.pdf" target="_blank" class="manual-button">ACARE Manual</a>
                    <a href="https://www3.nasa.gov/sites/default/files/atoms/files/2020_nasa_technology_taxonomy.pdf" target="_blank" class="manual-button">NASA Manual</a>
                </div>
            </form>
            <div class="loader" style="display: none;">Loading...</div>
        </div>

        <!-- Results Section -->
        <?php if (!empty($results)): ?>
            <div class="results-grid">
                <!-- ACARE Results -->
                <div class="box">
                    <div class="legend-container">
                        <div class="name-box"><b>ACARE Taxonomy Suggestions</b></div>
                    </div>
                    <?php foreach ($results['acare'] as $index => $result): ?>
                        <div class="demo-item">
                            <div class="label-box">Suggestion #<?php echo $index + 1; ?></div>
                            <div class="content-container">
                                <div class="info-group">
                                    <label>Domain:</label>
                                    <div class="readonly-field"><?php echo htmlspecialchars($result['domain']); ?></div>
                                </div>
                                <div class="info-group">
                                    <label>Subdomain:</label>
                                    <div class="readonly-field"><?php echo htmlspecialchars($result['subdomain']); ?></div>
                                </div>
                                <div class="info-group">
                                    <label>Similarity:</label>
                                    <div class="readonly-field"><?php echo number_format($result['similarity'], 3); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- NASA Results -->
                <div class="box">
                    <div class="legend-container">
                        <div class="name-box"><b>NASA Taxonomy Suggestions</b></div>
                    </div>
                    <?php foreach ($results['nasa'] as $index => $result): ?>
                        <div class="demo-item">
                            <div class="label-box">Suggestion #<?php echo $index + 1; ?></div>
                            <div class="content-container">
                                <div class="info-group">
                                    <label>Domain:</label>
                                    <div class="readonly-field"><?php echo htmlspecialchars($result['domain']); ?></div>
                                </div>
                                <div class="info-group">
                                    <label>Subdomain:</label>
                                    <div class="readonly-field"><?php echo htmlspecialchars($result['subdomain']); ?></div>
                                </div>
                                <div class="info-group">
                                    <label>Similarity:</label>
                                    <div class="readonly-field"><?php echo number_format($result['similarity'], 3); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            // Clear form fields on page load
            $('#classifyForm')[0].reset();
            
            // Show loader on form submit
            $('#classifyForm').on('submit', function() {
                $('.loader').show();
            });
            
            // Hide loader when results are loaded
            $('.loader').hide();
        });
    </script>

    <?php include('footer.php'); ?>
</body>
</html>