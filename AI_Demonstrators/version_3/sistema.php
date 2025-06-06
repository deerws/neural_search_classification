<?php
    include('DBconnect.php');
    include('loginCheck.php');
    include('php/functions.php');

    if ($logado) {
        if ($tipoUsuario === 'usuario') {
            $idSituation = [1,2,3,4,5];
        } elseif ($tipoUsuario === 'Laboratory') {
            $idSituation = [2];
        }
    } else {
        $idSituation = [2];
    }
        
    $idSituationString = implode(', ', $idSituation);

    // Sanitizar e obter valores dos filtros
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : "";
    $laboratoryFilter = isset($_GET['laboratory']) ? intval($_GET['laboratory']) : "";
    $categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : "";
    $trlFilter = isset($_GET['trl']) ? intval($_GET['trl']) : "";

    $filterClauses = [];
    $params = [];
    $types = "";

    // Construir cláusulas de filtro com prepared statements
    if (!empty($searchQuery)) {
        $searchParam = '%' . $searchQuery . '%';
        $filterClauses[] = "(p.idProj LIKE ? OR
                            p.pName LIKE ? OR
                            p.resp LIKE ? OR
                            p.idProj IN (SELECT pc.project_id FROM project_category pc 
                                        JOIN category c ON pc.category_id = c.idCat 
                                        WHERE c.Category LIKE ?) OR
                            p.id_TRL IN (SELECT t.idTRL FROM trl t WHERE t.TRL LIKE ?))";
        
        // Adicionar o mesmo parâmetro 5 vezes para cada LIKE
        for($i = 0; $i < 5; $i++) {
            $params[] = $searchParam;
            $types .= "s";
        }
    }

    if (!empty($laboratoryFilter)) {
        $filterClauses[] = "p.id_Laboratory = ?";
        $params[] = $laboratoryFilter;
        $types .= "i";
    }

    if (!empty($categoryFilter)) {
        $filterClauses[] = "p.idProj IN (SELECT project_id FROM project_category WHERE category_id = ?)";
        $params[] = $categoryFilter;
        $types .= "i";
    }

    if (!empty($trlFilter)) {
        $filterClauses[] = "p.id_TRL = ?";
        $params[] = $trlFilter;
        $types .= "i";
    }

    // Combinar cláusulas de filtro
    $filterClause = "";
    if (!empty($filterClauses)) {
        $filterClause = "AND (" . implode(" AND ", $filterClauses) . ")";
    }

    // Query para contar registros
    $countSql = "SELECT COUNT(*) as total FROM project p WHERE 
                p.sit_Project_id IN ($idSituationString) $filterClause";
    
    $countStmt = $link->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];
    $countStmt->close();

    $recordsPerPage = 4;
    $totalPages = ceil($totalRecords / $recordsPerPage);

    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($currentPage - 1) * $recordsPerPage;

    // Query principal com paginação
    $sql = "SELECT p.* FROM project p WHERE 
            p.sit_Project_id IN ($idSituationString) $filterClause
            ORDER BY p.pName ASC LIMIT ?, ?";
    
    $stmt = $link->prepare($sql);
    
    // Adicionar parâmetros de paginação
    $allParams = $params;
    $allParams[] = $offset;
    $allParams[] = $recordsPerPage;
    $allTypes = $types . "ii";
    
    if (!empty($allParams)) {
        $stmt->bind_param($allTypes, ...$allParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="css/general_template.css">
    <link rel="stylesheet" href="css/form_template.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Sistema | SC2C</title>
    <style>
        .no-results {
            text-align: center;
            padding: 80px 40px;
            color: #665;
            font-size: 18px;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .no-results-icon {
            font-size: 64px;
            margin-bottom: 30px;
            color: #ccc;
        }
        .filter-section label {
            color: white; /* Garante que os textos dos labels fiquem brancos */
        }
        .filter-section {
            background: #2B3B4B;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filter-row {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .clear-filters {
            margin-left: 10px;
        }
    </style>
</head>

<header class='header_nav'>
    <?php include('nav.php') ?>
</header>

<body>
    <div class="filter-section">
        <div class="filter-row">
            <div class="filter-group">
                <label for="searchBD">Search Projects:</label>
                <input type="search" class="form-control" placeholder="Search by name, ID, category, TRL or responsible..." id="searchBD" value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            
            <div class="filter-group">
                <label for="filterLab">Laboratory:</label>
                <select class="form-control" id="filterLab">
                    <option value="">All Laboratories</option>
                    <?php
                    $Laboratory = mysqli_query($link, "SELECT * FROM laboratory ORDER BY abb");
                    while ($l = mysqli_fetch_array($Laboratory)){
                        $selected = ($laboratoryFilter == $l['idLab']) ? 'selected' : '';
                        echo "<option value='{$l['idLab']}' $selected>{$l['abb']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filterCategory">Category:</label>
                <select class="form-control" id="filterCategory">
                    <option value="">All Categories</option>
                    <?php
                    $Category = mysqli_query($link, "SELECT * FROM category ORDER BY Category");
                    while ($c = mysqli_fetch_array($Category)){
                        $selected = ($categoryFilter == $c['idCat']) ? 'selected' : '';
                        echo "<option value='{$c['idCat']}' $selected>{$c['Category']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filterTRL">TRL Level:</label>
                <select class="form-control" id="filterTRL">
                    <option value="">All TRL Levels</option>
                    <?php
                    $TRL = mysqli_query($link, "SELECT * FROM trl ORDER BY idTRL");
                    while ($d = mysqli_fetch_array($TRL)){
                        $selected = ($trlFilter == $d['idTRL']) ? 'selected' : '';
                        echo "<option value='{$d['idTRL']}' $selected>{$d['TRL']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="filter-group" style="flex: 0;">
                <button onclick="applyFilters()" class="btn btn-primary">Apply Filters</button>
                <button onclick="clearFilters()" class="btn btn-default clear-filters">Clear</button>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2 class="table-title">Project Database</h2>
        
        <?php if ($totalRecords > 0): ?>
            <p style="color: white;">Found <?php echo $totalRecords; ?> project(s)</p>
            
            <table class='table table-bg'>
                <thead>
                    <tr>
                        <th class="column-header" scope="col">#</th>
                        <th class="column-header" scope="col">Name</th>
                        <th class="column-header" scope="col">
                            <div style="display: inline-block; margin: 0; padding: 0;">
                                Research Field
                                <a class="fcc-btn" href="https://sc2c.ufsc.br/aero/taxonomy/" style="margin-left: 5px;">?</a>
                            </div>
                        </th>
                        <th class="column-header" scope="col">
                            <div style="display: inline-block; margin: 0; padding: 0;">
                                TRL
                                <a class="fcc-btn" href="https://sc2c.ufsc.br/aero/category/trl/" style="margin-left: 5px;">?</a>
                            </div>
                        </th>
                        <th class="column-header" scope="col">Laboratory</th>
                        <th class="column-header" scope="col">Responsible</th>
                        <?php
                        if ($logado) {
                            if ($tipoUsuario === 'usuario') {
                                echo "<th scope='col'>Insert Date</th>";
                                echo "<th scope='col'>Situation</th>";
                            }
                        }
                        ?>             
                        <th scope="col">Options</th>
                    </tr>
                </thead>       
                <tbody>
                    <?php
                    while ($row = $result->fetch_assoc()) {
                        $idProj = $row['idProj'];
                        $projName = $row['pName'];
                        $projTRL = $row['id_TRL'];
                        $idLab = $row['id_Laboratory'];
                        $projDescr = $row['descr'];
                        $projLink = $row['link_ref'];
                        $projEmail = $row['email'];
                        $projResp = $row['resp'];
                        $projSitId = $row['sit_project_id'];
                        $projDate = $row['date_in'];
                        
                        echo "<tr>";
                        echo "<td>$idProj</td>";
                        echo "<td><a href='viewProject.php?id=$idProj'>" . htmlspecialchars($projName) . "</a></td>";
                        echo "<td>" . getProjectCategories($idProj, $link) . "</td>";
                        echo "<td>" . getNameTrl($projTRL) . "</td>";
                        echo "<td><a href='viewLab.php?id=$idLab'>" . getNameLab($idLab) . "</a></td>";
                        echo "<td>" . htmlspecialchars($projResp) . "</td>";

                        if ($logado && $tipoUsuario === 'usuario') {
                            echo "<td>$projDate</td>";

                            $statusIcon = "";
                            $statusText = "";

                            switch ($projSitId) {
                                case 1:
                                    $statusText = "Inactive";
                                    $statusIcon = "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-exclamation-circle-fill' viewBox='0 0 16 16'><path d='M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z'/></svg>";
                                    break;
                                case 2:
                                    $statusText = "Active";
                                    $statusIcon = "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-check-circle-fill' viewBox='0 0 16 16'><path d='M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z'/></svg>";
                                    break;
                                case 3:
                                    $statusText = "Waiting Confirmation";
                                    $statusIcon = "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-clock-fill' viewBox='0 0 16 16'><path d='M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z'/></svg>";
                                    break;
                                case 4:
                                    $statusText = "Invisible";
                                    $statusIcon = "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-eye-slash-fill' viewBox='0 0 16 16'><path d='m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z'/><path d='M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z'/></svg>";
                                    break;
                                case 5:
                                    $statusText = "Waiting Correction";
                                    $statusIcon = "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-eye-slash-fill' viewBox='0 0 16 16'><path d='m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z'/><path d='M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z'/></svg>";
                                    break;
                            }

                            echo "<td>$statusText $statusIcon</td>";
                        }

                        if ($logado) {
                            echo "<td>";
                            echo "<a class='btn btn-primary btn-sm' title='View project' href='viewProject.php?id=$idProj'>📖</a> ";

                            if ($tipoUsuario === 'usuario') {
                                echo "<a class='btn btn-primary btn-sm' title='Edit project' href='editProject.php?id=$idProj'>✏️</a> ";
                                echo "<a class='btn btn-primary btn-sm' title='Edit demonstrators' href='editDemonstrator.php?id=$idProj'>🖼️</a> ";
                                echo "<a class='btn btn-danger btn-sm' title='Delete project' href='deleteProj.php?id=$idProj'>🗑️</a>";
                            }

                            echo "</td>";
                        } else {
                            echo "<td><a class='btn btn-primary btn-sm' title='View project' href='viewProject.php?id=$idProj'>📖</a></td>";
                        }

                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
                <ul class="pagination">
                    <?php
                    $maxVisiblePages = 3;
                    $startPage = max($currentPage - floor($maxVisiblePages / 2), 1);
                    $endPage = min($startPage + $maxVisiblePages - 1, $totalPages);

                    // Função para construir URL com parâmetros
                    function buildUrl($page, $search, $lab, $cat, $trl) {
                        $url = 'sistema.php?page=' . $page;
                        if (!empty($search)) $url .= '&search=' . urlencode($search);
                        if (!empty($lab)) $url .= '&laboratory=' . urlencode($lab);
                        if (!empty($cat)) $url .= '&category=' . urlencode($cat);
                        if (!empty($trl)) $url .= '&trl=' . urlencode($trl);
                        return $url;
                    }

                    if ($startPage > 1) {
                        $url = buildUrl(1, $searchQuery, $laboratoryFilter, $categoryFilter, $trlFilter);
                        echo '<li><a href="' . $url . '">&laquo;</a></li>';
                    }

                    for ($page = $startPage; $page <= $endPage; $page++) {
                        $url = buildUrl($page, $searchQuery, $laboratoryFilter, $categoryFilter, $trlFilter);
                        $activeClass = ($page == $currentPage) ? 'active' : '';
                        echo '<li class="' . $activeClass . '"><a href="' . $url . '">' . $page . '</a></li>';
                    }

                    if ($endPage < $totalPages) {
                        $url = buildUrl($totalPages, $searchQuery, $laboratoryFilter, $categoryFilter, $trlFilter);
                        echo '<li><a href="' . $url . '">&raquo;</a></li>';
                    }
                    ?>
                </ul>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Mensagem quando não há resultados -->
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3>No Results Found</h3>
                <p>We couldn't find any projects matching your search criteria.</p>
                <p>Try adjusting your filters or search terms.</p>
                <button onclick="clearFilters()" class="btn btn-primary">Clear All Filters</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Função para obter parâmetro da URL
        function getParameterByName(name) {
            var url = window.location.href;
            name = name.replace(/[\[\]]/g, '\\$&');
            var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
                results = regex.exec(url);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, ' '));
        }

        // Carregar valores dos filtros da URL ao carregar a página
        window.onload = function() {
            var searchQuery = getParameterByName('search');
            var labFilter = getParameterByName('laboratory');
            var catFilter = getParameterByName('category');
            var trlFilter = getParameterByName('trl');
            
            if (searchQuery) document.getElementById('searchBD').value = searchQuery;
            if (labFilter) document.getElementById('filterLab').value = labFilter;
            if (catFilter) document.getElementById('filterCategory').value = catFilter;
            if (trlFilter) document.getElementById('filterTRL').value = trlFilter;
        };

        // Event listener para busca com Enter
        document.getElementById('searchBD').addEventListener("keydown", function(event) { 
            if (event.key === "Enter") {
                applyFilters();
            }
        });

        // Aplicar filtros
        function applyFilters() {
            var searchQuery = encodeURIComponent(document.getElementById('searchBD').value.trim());
            var laboratoryFilter = document.getElementById('filterLab').value;
            var categoryFilter = document.getElementById('filterCategory').value;
            var trlFilter = document.getElementById('filterTRL').value;

            var url = 'sistema.php?page=1';

            if (searchQuery) {
                url += '&search=' + searchQuery;
            }
            if (laboratoryFilter) {
                url += '&laboratory=' + laboratoryFilter;
            }
            if (categoryFilter) {
                url += '&category=' + categoryFilter;
            }
            if (trlFilter) {
                url += '&trl=' + trlFilter;
            }

            window.location.href = url;
        }

        // Limpar todos os filtros
        function clearFilters() {
            document.getElementById('searchBD').value = '';
            document.getElementById('filterLab').value = '';
            document.getElementById('filterCategory').value = '';
            document.getElementById('filterTRL').value = '';
            window.location.href = 'sistema.php';
        }
    </script>

    <?php include('footer.php') ?>
</body>
</html>

<?php
$stmt->close();
$link->close();
?>