<?php
// ==========================================
// Charneco Samuel
// 25.05.2020
// Version 1.0
// Site de critique de livres
// ==========================================

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
    <link rel="stylesheet" type="text/css" href="css/profil.css">
    <title>Ta Bibliothèque</title>
</head>
    <body>
        <nav>
            <?php
                echo ConnectForm();
            ?>
            <button class="btnHome"><a href="index.php">Accueil</a></button>
        </nav>
        <h1>Mon profil</h1>
        <?php
            echo ProfilDetailsForm();
            if(isset($_SESSION["msg"])){ echo $_SESSION["msg"]; }
        ?>
        <h2>Critiques en attente de validation</h2>
        <?php           
            echo ShowNotValidReview();
        ?>
        <h2>Vos critiques</h2>
        <?php
            if(isset($_SESSION["msgEditReview"])){ echo $_SESSION["msgEditReview"]; }

            echo ShowValidReview();
        ?>
    </body>
</html>