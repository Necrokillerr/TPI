<!-- ==========================================
// Charneco Samuel
// 25.05.2020
// Version 1.0
// Site de critique de livres
// ========================================== -->
<?php
    require 'lib.inc.php';
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
            <button><a href="index.php">Acceuil</a></button>
        </nav>
        <?php
            echo ProfilDetailsForm();
        ?>
        <h2>Critiques en attente de validation</h2>
        <?php
            echo ShowNotValidReview();
        ?>
        <h2>Vos critiques</h2>
        <?php
            echo ShowValidReview();
        ?>
    </body>
</html>