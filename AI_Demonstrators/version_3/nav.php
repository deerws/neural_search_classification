<link rel="stylesheet" href="css/nav.css">
<link rel="stylesheet" href="css/general_template.css">
<link rel="stylesheet" href="css/form_template.css">
<a href="https://sc2c.ufsc.br/">
    <img src="images/logo_sc2c_horizontal.png" alt="Logo SC2C" class="logo" width="220px">
</a>
<body>
    <nav class="main-menu">
        <ul class="nav_links">
            <?php
            if ($logado) {
                echo '<li class="li_nav"><a class="a_nav" href="sistema.php">Projects</a></li>';
                echo '<li class="li_nav"><a class="a_nav" href="sistemaLab.php">Laboratories</a></li>';
                if ($tipoUsuario === 'usuario') {
                    echo '<li class="li_nav"><a class="a_nav" href="registerUser.php">New Admin</a></li>';
                    echo '<li class="li_nav"><a class="a_nav" href="newLab.php">Invite Laboratory</a></li>';
                    echo '<li class="li_nav"><a class="a_nav" href="approveProjects.php">Approve Projects</a></li>';
                } elseif ($tipoUsuario === 'Laboratory') {
                    echo '<li class="li_nav"><a class="a_nav" href="myProjects.php">My Projects</a></li>';
                    echo '<li class="li_nav"><a class="a_nav" href="editLaboratory.php">Laboratory Info</a></li>';
                }
                echo '<li class="li_nav"><a class="a_nav" href="registerProject.php">Register Project</a></li>';
                echo '<li class="li_nav"><a class="a_nav" href="classifyTaxonomy.php">Classify Taxonomy</a></li>';
            } else {
                echo '<li class="li_nav"><a class="a_nav" href="home.php">Home</a></li>';
                echo '<li class="li_nav"><a class="a_nav" href="login.php">Login</a></li>';
            }
            ?>
        </ul>
    </nav>
    <nav class="body_nav">
        <ul class="nav_links">
            <?php
            if ($logado) {
                echo '<li class="li_nav"><a class="a_nav" href="sistema.php">Projects</a></li>';
                echo '<li class="li_nav"><a class="a_nav" href="sistemaLab.php">Laboratories</a></li>';
                if ($tipoUsuario === 'usuario') {
                    echo '<li class="li_nav"><a class="a_nav" href="registerUser.php">New Admin</a></li>';
                    echo '<li class="li_nav"><a class="a_nav" href="newLab.php">Invite Laboratory</a></li>';
                    echo '<li class="li_nav"><a class="a_nav" href="approveProjects.php">Approve Projects</a></li>';
                } elseif ($tipoUsuario === 'Laboratory') {
                    echo '<li class="li_nav"><a class="a_nav" href="myProjects.php">My Projects</a></li>';
                    echo '<li class="li_nav"><a class="a_nav" href="editLaboratory.php">Laboratory Info</a></li>';
                }
                echo '<li class="li_nav"><a class="a_nav" href="registerProject.php">Register Project</a></li>';
                echo '<li class="li_nav"><a class="a_nav" href="classifyTaxonomy.php">Classify Taxonomy</a></li>';
            } else {
                echo '<li class="li_nav"><a class="a_nav" href="home.php">Home</a></li>';
                echo '<li class="li_nav"><a class="a_nav" href="login.php">Login</a></li>';
            }
            ?>
        </ul>
        <div onclick="open_menu()" class="main-menu-btn" style="cursor: pointer;"><i class="fas fa-bars"></i></div>
    </nav>
</body>
<?php
if ($logado) { 
    echo '<a class="a_nav" href="logout.php"><button class="out">Logout</button></a>';
}
?>

<script>
    function open_menu() {
        var mainMenu = document.querySelector('.main-menu');
        mainMenu.style.display = (mainMenu.style.display === 'block') ? 'none' : 'block';
    }
</script>