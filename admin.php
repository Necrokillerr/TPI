<?php
// ==========================================
// Charneco Samuel
// 25.05.2020
// Version 1.0
// Site de critique de livres
// ==========================================

    if(session_status() == PHP_SESSION_NONE){
        session_start(); 
    }

    require 'lib.inc.php';

    if(!$_SESSION["isAdmin"]){
        header("Location: index.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/review.css">
    <link rel="stylesheet" type="text/css" href="css/nav.css">
    <link rel="stylesheet" type="text/css" href="css/admin.css">
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
            echo AddBookForm();
            if(isset($_SESSION["msgAddBook"])){ echo $_SESSION["msgAddBook"]; }

            if(isset($_SESSION["adminEdit"]) && $_SESSION["adminEdit"] == true){
                echo UpdateBookForm();
            }
            if(isset($_SESSION["msgUpdateBook"])){ echo $_SESSION["msgUpdateBook"]; }
        ?>
        <h2>Critiques en attente</h2>
        <?php
            echo ShowAllNotValidReview();
        ?>
    </body>
</html>