<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <title>Ta Bibliothèque</title>
</head>
    <body>  
        <form method="POST">
            <h2>Connexion</h2>
            <label>Pseudo</label>
            <input type="text" name="tbxLoginNickname" placeholder="Entrez votre pseudo">
            <label>Mot de passe</label>
            <input type="password" name="tbxLoginPassword" placeholder="Entrez votre mot de passe">
            <input type="submit" name="btnLogin" value="Login">
            <a href="register.php">s'inscrire</a>
            <?php
                if(isset($_SESSION["msg"])) { echo $_SESSION["msg"]; }
            ?>
        </form>
    </body>
</html>