<?php
    require 'lib.inc.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/review.css">
    <link rel="stylesheet" type="text/css" href="css/admin.css">
    <title>Ta Bibliothèque</title>
</head>
    <body>
        <?php
            echo AddBookForm();
        ?>
        <h2>Critiques en attente</h2>
        <?php
            echo ShowAllNotValidReview();
        ?>
    </body>
</html>