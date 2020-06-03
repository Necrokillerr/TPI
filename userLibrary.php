<!-- ==========================================
// Charneco Samuel
// 25.05.2020
// Version 1.0
// Site de critique de livres
// ========================================== -->
<?php
    require 'lib.inc.php';

    if(!$_SESSION["IsConnected"]){
        header("Location: index.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/nav.css">
    <link rel="stylesheet" type="text/css" href="css/userLibrary.css">
    <link rel="stylesheet" type="text/css" href="css/index.css">
    <title>Ta Bibliothèque</title>
</head>
    <body>
        <nav>
            <?php
                echo ConnectForm();
            ?>
            <button class="btnHome"><a href="index.php">Accueil</a></button>
        </nav>    
        <?php
            echo ShowFavBooksForm();
        ?>
    </body>
</html>