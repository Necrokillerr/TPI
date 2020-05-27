<?php
    if(session_status() == PHP_SESSION_NONE){
        session_start(); 
    }

    require 'lib.inc.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/register.css">
    <title>Ta Bibliothèque</title>
</head>
    <body>  
        <form method="POST">
            <h2>Inscription</h2>
            <label>Pseudo</label>
            <input type="text" name="tbxRegisterNickname" placeholder="Entrez un pseudo">
            <label>E-mail</label>
            <input type="email" name="tbxRegisterEmail" placeholder="Entrez un e-mail">
            <label>Mot de passe</label>
            <input type="password" name="tbxRegisterPassword" placeholder="Entrez un mot de passe">
            <label>Confirmer mot de passe</label>
            <input type="password" name="tbxRegisterConfirmPassword" placeholder="Confirmez le mot de passe">
            <input type="submit" name="btnRegister" value="S'enregistrer">
            <a href="login.php">se connecter</a>
            <?php
                if(isset($_SESSION["msg"])) { echo "<p>".$_SESSION["msg"]."</p>"; }
            ?>
        </form>
    </body>
</html>