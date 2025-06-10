<?php
    include('loginCheck.php');
    include('DBconnect.php');
    include('php/functions.php');

    // Depuração: Habilitar erros para verificar problemas
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (!empty($_GET['id'])) {
        $id = $_GET['id'];
        $sqlSelect = "SELECT * FROM laboratory WHERE idLab=?";
        $stmt = $link->prepare($sqlSelect);
        if ($stmt === false) {
            die("Erro na preparação da query de laboratório: " . $link->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($idLab, $nameLab, $abbLab, $descrLab, $linkLab,
            $emailLab, $phoneLab, $addressLab, $cepLab, $coordLab, $logoLab, $passwordLab,
            $keyLab, $sitLab, $dateLab);

        if ($stmt->fetch()) {
            // Depuração: Verificar valor de $logoLab
            echo "<!-- Debug: logoLab = $logoLab -->";
            $stmt->close();
        } else {
            $stmt->close();
            header('Location: sistemaLab.php');
            exit();
        }
    } else {
        header('Location: sistemaLab.php');
        exit();
    }

    $idSituation = [2]; // Pode ajustar para [1,2,3,4,5] se necessário
    $idSituationString = implode(', ', $idSituation);

    // Query para listar todos os projetos do laboratório
    $sql = "SELECT idProj, pName, id_TRL, id_Laboratory, resp, sit_Project_id, date_in 
            FROM project 
            WHERE id_Laboratory = ? 
            ORDER BY pName DESC";

    $stmt = $link->prepare($sql);
    if ($stmt === false) {
        die("Erro na preparação da query de projetos: " . $link->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($idProj, $projName, $projTRL, $idLab, $projResp, $projSitId, $projDate);
    $stmt->store_result();

    $projects = [];
    while ($stmt->fetch()) {
        $projects[] = [
            'idProj' => $idProj,
            'pName' => $projName,
            'id_TRL' => $projTRL,
            'id_Laboratory' => $idLab,
            'resp' => $projResp,
            'sit_Project_id' => $projSitId,
            'date_in' => $projDate
        ];
    }
    $stmt->close();

    // Depuração: Verificar número de projetos retornados
    echo "<!-- Debug: Número de projetos = " . count($projects) . " -->";
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
    <title>View Lab | SC2C</title>
</head>

<header class='header_nav'>
    <?php include('nav.php') ?>
</header>

<body>
    <div class="box">
        <div class="legend-container">
            <div class="name-box"><b><?php echo $abbLab ?> - </b><b><?php echo $nameLab ?></b></div>
            <?php if (!empty($logoLab)) { ?>
                <img class="logolab" src="dbImages/Logos/<?php echo htmlspecialchars($logoLab); ?>" 
                     onerror="this.style.display='none'; console.log('Erro ao carregar imagem: dbImages/Logos/<?php echo $logoLab; ?>');">
            <?php } else { ?>
                <p><!-- Debug: Nenhum logo encontrado para $logoLab --></p>
            <?php } ?>
        </div>
        <br>
        <div class="content-container">               
            <label for="resp">Laboratory Coordinator:</label>
            <br>
            <div class='readonly-field'><?php echo $coordLab; ?></div>
            <br><br>
            <label for="Description">Laboratory Description:</label>
            <br>
            <div class='description-box'><?php echo $descrLab; ?></div>
            <br><br>
        </div>                       
    </div>

    <div class="projects">      
        <div class="table-container">
            <table class='table table-bg'>
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Name</th>
                        <th scope="col">Research Field</th>
                        <th scope="col">TRL</th>
                        <th scope="col">Responsible</th>
                        <th scope="col">...</th>
                    </tr>
                </thead>       
                <tbody>
                    <?php
                    if (empty($projects)) {
                        echo "<tr><td colspan='6'>Nenhum projeto encontrado para este laboratório.</td></tr>";
                    } else {
                        foreach ($projects as $row) {
                            $idProj = $row['idProj'];
                            $projName = $row['pName'];
                            $projTRL = $row['id_TRL'];
                            $projResp = $row['resp'];
                            
                            echo "<tr>";
                            echo "<td>$idProj</td>";
                            echo "<td><a href='viewProject.php?id=$idProj'>" . htmlspecialchars($projName) . "</a></td>";
                            echo "<td>" . getProjectCategories($idProj, $link) . "</td>";
                            echo "<td>" . getNameTrl($projTRL) . "</td>";
                            echo "<td>" . htmlspecialchars($projResp) . "</td>";

                            echo "<td class='actions-cell'>  
                                <a title='Preview project' class='btn btn-primary btn-sm' href='viewProject.php?id=$idProj'>
                                <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-book-half' viewBox='0 0 16 16'>
                                    <path d='M8.5 2.687c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z'/>
                                </svg>
                                </a>
                            </td>";
                        
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>  
</body>

<?php include('footer.php') ?>
</html>

<?php
$link->close();
?>