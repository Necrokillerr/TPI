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
    <link rel="stylesheet" type="text/css" href="css/review.css">
    <link rel="stylesheet" type="text/css" href="css/nav.css">
    <link rel="stylesheet" type="text/css" href="css/bookDetail.css">
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
            echo BookDetailsForm();
            if(isset($_SESSION["IsConnected"])){
                echo ShowReviewForm();
            }
            else{
                echo "<h4>Connectez-vous pour pouvoir rédiger une critique !</h4>";
            }
            
            if(isset($_SESSION["msgAddReview"])){ echo $_SESSION["msgAddReview"]; }         
        ?>
        <h2>Critiques du livre</h2>
        <?php
            echo ShowValidReviewOfBook();
        ?>
    </body>
</html>