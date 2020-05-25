<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/index.css">
    <title>Ta Bibliothèque</title>
</head>
    <body>
        <nav>
            <?php
                //echo ConnectForm();
            ?>
            <div class="dropdown">
                <button class="dropdownStyle">Livres</button>
                <div class="dropdown-child">
                    <form method="POST">
                        <div class="dropdown-abc">
                            <button class="dropdownStyle" type="submit" name="sortBooks" value="ABC">Ordre Alphabétique</button>
                            <div class="dropdown-child-abc">
                                <button class="dropdownStyle" type="submit" name="sortBooks" value="Titre">Titre</button>
                                <button class="dropdownStyle" type="submit" name="sortBooks" value="Auteur">Auteur</button>
                                <button class="dropdownStyle" type="submit" name="sortBooks" value="Editeur">Editeur</button>
                            </div>
                        </div>                        
                    </form>
                </div>
            </div>
        </nav>
    </body>
</html>