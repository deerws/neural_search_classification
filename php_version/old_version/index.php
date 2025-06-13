<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Approval Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Approval Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Upload CSV</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2>Classification Approvals</h2>
        <!-- Filter Form -->
        <form method="GET" action="index.php" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <label for="programa" class="form-label">Programa</label>
                    <select name="programa" id="programa" class="form-select">
                        <option value="">All</option>
                        <?php
                        // Populate Programa options
                        $programas = array_unique(array_column(parseCSV(), 'Programa'));
                        foreach ($programas as $prog) {
                            echo "<option value='$prog'>" . htmlspecialchars($prog) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="user" class="form-label">User</label>
                    <select name="user" id="user" class="form-select">
                        <option value="">All</option>
                        <option value="Andr">Andr</option>
                        <option value="JOAO">JOAO</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-2">Filter</button>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Programa</th>
                        <th>Área</th>
                        <th>Linha de Pesquisa</th>
                        <th>ACARE 1</th>
                        <th>NASA 1</th>
                        <th>Approval 1</th>
                        <th>Status 1</th>
                        <th>User</th>
                        <th>Conflict</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include 'process.php';
                    $data = parseCSV();
                    $filters = [
                        'programa' => $_GET['programa'] ?? '',
                        'status' => $_GET['status'] ?? '',
                        'user' => $_GET['user'] ?? ''
                    ];
                    $filteredData = filterData($data, $filters);

                    foreach ($filteredData as $row) {
                        $approvals = explode(';', $row['Approved Classification']);
                        $status = preg_match('/(Approved|Rejected)/', $approvals[0] ?? '', $match) ? $match[0] : '';
                        $users = explode(';', $row['User']);
                        $conflict = detectConflict($row['Linha de Pesquisa'], $data);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Programa']); ?></td>
                            <td><?php echo htmlspecialchars($row['Área']); ?></td>
                            <td><?php echo htmlspecialchars($row['Linha de Pesquisa']); ?></td>
                            <td class="acare"><?php echo htmlspecialchars(shortenText($row['ACARE 1'])); ?></td>
                            <td class="nasa"><?php echo htmlspecialchars(shortenText($row['NASA 1'])); ?></td>
                            <td class="approval"><?php echo htmlspecialchars($approvals[0] ?? ''); ?></td>
                            <td class="status-<?php echo strtolower($status); ?>"><?php echo $status; ?></td>
                            <td class="user-<?php echo strtolower($users[0] ?? ''); ?>">
                                <?php echo htmlspecialchars($row['User']); ?>
                            </td>
                            <td class="conflict-<?php echo $conflict ? 'yes' : 'no'; ?>">
                                <?php echo $conflict ? 'Conflict' : 'No Conflict'; ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function parseCSV() {
    $file = 'merged_classification_approval.csv';
    $data = [];
    if (($handle = fopen($file, 'r')) !== false) {
        $headers = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, $row);
        }
        fclose($handle);
    }
    return $data;
}
?>