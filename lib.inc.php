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

require "db/credentials.php";

$_SESSION["msg"] = "";
$_SESSION["msgAddBook"] = "";
$_SESSION["msgAddReview"] = "";
$_SESSION["msgUpdateBook"] = "";
$_SESSION["msgSearch"] = "";
$_SESSION["msgEditReview"] = "";
$_SESSION["msgFav"] = "";
$_SESSION["savedReview"] = "";
$salt = "12983476";

// ----------------------------------------------------------------------------------------------------------
// ------------------------------------- CONNEXION À LA BASE DE DONNÉES -------------------------------------
// ----------------------------------------------------------------------------------------------------------
/**
 * Connection à la base de données
 * 
 * Retourne un objet PDO
 */
function ConnectDB(){
    static $db = null;

    if($db == null){
        try{
            $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port='. DB_PORT, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e) {
            die('Échec lors de la connexion : ' . $e->getMessage());
        }
    }
    return $db;
}

// ---------------------------------------------------------------------------------------------------------
// ------------------------------------- PAGE LOGIN / PAGE INSCRIPTION -------------------------------------
// ---------------------------------------------------------------------------------------------------------
/**
 * Compare les champs avec la base de données
 * 
 * Param : $pseudo (pseudo de l'utilisateur)
 *         $password (mot de passe de l'utilisateur)
 * 
 * Retourne true ou false
 */
function LoginUser($pseudo, $password){
    $db = ConnectDB();
    $sql = $db->prepare("SELECT `pseudo`, `password`, `admin` FROM users WHERE pseudo = :Pseudo");    
    try{
        $sql->execute(array(
        ':Pseudo' => $pseudo,
        ));
    } catch(Exception $e) {
        echo 'Connexion impossible : ',  $e->getMessage(), "\n";   
        exit();     
    }
    $result = $sql->fetch();
    if($password == $result[1]){
        $_SESSION["StockedNickname"] = $pseudo;
        $_SESSION["IsConnected"] = true;
        if($result[2] == 1){
            $_SESSION["isAdmin"] = true;
        }
        return true;
    }
    else{
        return false;
    }
}

/**
 * Insertion d'un nouveau user
 * 
 * Param : $pseudo (pseudo de l'utilisateur)
 *         $email (e-mail de l'utilisateur)
 *         $password (mot de passe hasher de l'utilisateur)
 * 
 * Retourne true ou false
 */
function InsertUser($pseudo, $email, $password){
    $db = ConnectDB();
    $sql = $db->prepare("INSERT INTO users (`pseudo`, `password`, `email`, `admin`) VALUES (:Pseudo, :Pwd, :Email, false)");   
    try{
        $sql->execute(array(
            ':Pseudo' => $pseudo,
            ':Pwd' => $password,
            ':Email' => $email,            
        ));
    } catch (Exception $e) {
        return false;
    }
    return true;
}

// ---------------------------------------------------------------------------------------------------------
// -------------------------------------- PAGE PRINCIPALE (index.php) --------------------------------------
// ---------------------------------------------------------------------------------------------------------
/**
 * Récupère les livres selon le filtre choisi
 * 
 * Retourne un tableau
 */
function GetAllBooks(){
    $db = ConnectDB();
    // Filtre par ordre alphabétique - Titre
   if(isset($_SESSION["sortBooks"]) && $_SESSION["sortBooks"] == "Titre"){
        $sql = $db->prepare("(
            SELECT COUNT(idReview) AS nbReviews, ROUND(AVG(mark), 1) AS mark, reviews.isbn, title, author, editor, editionDate, image
            FROM reviews 
            JOIN books ON reviews.isbn = books.isbn
            GROUP BY reviews.isbn
            )
            UNION
            (
            SELECT null, null AS mark, books.isbn, title, author, editor, editionDate, image
            FROM books
            WHERE books.isbn NOT IN (
                SELECT books.isbn FROM books
                JOIN reviews ON books.isbn = reviews.isbn
                GROUP BY reviews.isbn
            )
            ) ORDER BY title ASC");
    }
    // Filtre par ordre alphabétique - Auteur
    else if(isset($_SESSION["sortBooks"]) && $_SESSION["sortBooks"] == "Auteur"){
        $sql = $db->prepare('(
            SELECT COUNT(idReview) AS nbReviews, ROUND(AVG(mark), 1) AS mark, reviews.isbn, title, author, editor, editionDate, image
            FROM reviews 
            JOIN books ON reviews.isbn = books.isbn
            GROUP BY reviews.isbn
            )
            UNION
            (
            SELECT null, null AS mark, books.isbn, title, author, editor, editionDate, image
            FROM books
            WHERE books.isbn NOT IN (
                SELECT books.isbn FROM books
                JOIN reviews ON books.isbn = reviews.isbn
                GROUP BY reviews.isbn
            )
            ) ORDER BY author ASC');
    }
    // Filtre par ordre alphabétique - Editeur
    else if(isset($_SESSION["sortBooks"]) && $_SESSION["sortBooks"] == "Editeur"){
        $sql = $db->prepare('(
            SELECT COUNT(idReview) AS nbReviews, ROUND(AVG(mark), 1) AS mark, reviews.isbn, title, author, editor, editionDate, image
            FROM reviews 
            JOIN books ON reviews.isbn = books.isbn
            GROUP BY reviews.isbn
            )
            UNION
            (
            SELECT null, null AS mark, books.isbn, title, author, editor, editionDate, image
            FROM books
            WHERE books.isbn NOT IN (
                SELECT books.isbn FROM books
                JOIN reviews ON books.isbn = reviews.isbn
                GROUP BY reviews.isbn
            )
            ) ORDER BY editor ASC');
    }
    // Sans filtre
    else{
        $sql = $db->prepare("(
            SELECT COUNT(idReview) AS nbReviews, ROUND(AVG(mark), 1) AS mark, reviews.isbn, title, author, editor, editionDate, image
            FROM reviews 
                JOIN books
                ON reviews.isbn = books.isbn
            GROUP BY reviews.isbn
            )
            UNION
            (
            SELECT null, null AS mark, books.isbn, title, author, editor, editionDate, image
            FROM books
            WHERE books.isbn NOT IN(
                SELECT books.`isbn` FROM books
                    JOIN reviews
                    ON books.isbn = reviews.isbn
            GROUP BY reviews.`isbn`
            )
        )");
    }
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

/**
 * Affiche les livres récupéré par la fonction GetAllBooks()
 * 
 * Retourne de l'HTML
 */
function ShowAllBooks(){
    $books = GetAllBooks();
    $tab = null;
    if(empty($books)){
        echo "<br><h3>Aucun livres trouvé</h3>";
    }
    else{
        foreach($books as $key => $value){            
            $tab .= <<<EX
                <div class="allBooksContainer">
                    <div class="bookContainer">
                        <div class="bookImg">
                            <img src="img/{$value['image']}"/>
                        </div>
                        <div class="bookTitle">
                            <strong>
                                <a name="link" href="bookDetail.php?id={$value['isbn']}">{$value['title']}</a>
                            </strong>
                        </div>
                        <div class="bookScoreFav">
                            <label>Auteur : {$value['author']}</label><br>
                            <label>Editeur : {$value['editor']}</label><br>
                            <label>Nombre de critiques : {$value['nbReviews']}</label><br>
                            <label>Note : {$value['mark']}</label><br>
EX;

            if(isset($_SESSION["isAdmin"])){
                $tab .= <<<EX
                <form method="POST">
                    <button value="{$value["isbn"]}" name="btnAdminEdit">Modifier</button>
                    <button value="{$value["isbn"]}" name="btnAdminDelete">Supprimer</button>
                </form>
EX;

            }
            $tab .= "</div></div></div>";
        }
    }   
    return $tab;
}
/**
 * Récupère le nombre de critiques d'un livre
 * 
 * Retourne de l'HTML
 */
function GetNumberOfReviews(){
    $db = ConnectDB();
    $sql = $db->prepare('');
    $sql->bindParam(':searching', $_SESSION["search"]);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

/**
 * Affiche le formulaire de recherche d'un livre
 * 
 * Retourne de l'HTML
 */
function SearchForm(){
    $form = null;
    $form .= <<<EX
    <form method="POST">
EX;
        if(filter_has_var(INPUT_POST, "btnShowSearchForm")){
            
                $form .= <<<EX
                    <input type="search" name="tbxSearchTitle" placeholder="Rechercher un titre">
                    <input type="search" name="tbxSearchAuthor" placeholder="Rechercher un auteur">
                    <input type="search" name="tbxSearchEditor" placeholder="Rechercher un editeur">
                    <input type="submit" name="btnSearch" value="Rechercher">
EX;
           
        }
        else{
            $form .= <<<EX
            <input type="submit" name="btnShowSearchForm" value="Faire une recherche">
            <input type="submit" name="btnResetFilter" value="Retirer filtre">
EX;
        }
        
    $form .= <<<EX
    </from>
EX;
    return $form;
}

/**
 * Récupère les livres qui concerne la recherche de l'utilisateur
 * 
 * Retourne un tableau
 */
function Search(){
    $db = ConnectDB();
    $sql = $db->prepare('(
        SELECT COUNT(idReview) AS nbReviews, ROUND(AVG(mark), 1) AS mark, reviews.isbn, title, author, editor, editionDate, image
        FROM reviews 
        JOIN books ON reviews.isbn = books.isbn
        WHERE title LIKE :Title AND author LIKE :Author AND editor LIKE :Editor
        GROUP BY reviews.isbn
        )
        UNION
        (
        SELECT null, null AS mark, books.isbn, title, author, editor, editionDate, image
        FROM books
        WHERE books.isbn NOT IN (
        SELECT books.isbn FROM books
        JOIN reviews ON books.isbn = reviews.isbn
        GROUP BY reviews.isbn
        )
        AND title LIKE :Title AND author LIKE :Author AND editor LIKE :Editor
        )       
        ');
    $sql->bindParam(':Title', $_SESSION["searchTitle"]);
    $sql->bindParam(':Author', $_SESSION["searchAuthor"]);
    $sql->bindParam(':Editor', $_SESSION["searchEditor"]);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

/**
 * Affiche les livres trouvé par la fonction Search()
 * 
 * Retourne de l'HTML
 */
function FindedForm(){
    $finded = Search();
    $tab = null;
    if(empty($finded)){
        $_SESSION["msgSearch"] = "<br><h3>Aucun livres trouvé</h3><p><h3>Réessayer votre recherche avec moins de filtre !</h3></p>";
    }
    foreach($finded as $key => $value){            
        $tab .= <<<EX
        <div class="allBooksContainer">
                    <div class="bookContainer">
                        <div class="bookImg">
                            <img src="img/{$value['image']}"/>
                        </div>
                        <div class="bookTitle">
                            <strong>
                                <a name="link" href="bookDetail.php?id={$value['isbn']}">{$value['title']}</a>
                            </strong>
                        </div>
                        <div class="bookScoreFav">
                            <label>Auteur : {$value['author']}</label><br>
                            <label>Editeur : {$value['editor']}</label><br>
                            <label>Nombre de critiques : {$value['nbReviews']}</label><br>
                            <label>Note : {$value['mark']}</label>
EX;
            if(isset($_SESSION["isAdmin"])){
                $tab .= <<<EX
                <form method="POST">
                    <button value="{$value["isbn"]}" name="btnAdminEdit">Modifier</button>
                    <button value="{$value["isbn"]}" name="btnAdminDelete">Supprimer</button>
                </form>
EX;
        }
        $tab .= "</div></div></div>";
    }
    return $tab;
}

// ----------------------------------------------------------------------------------------------------------
// ----------------------------------------------- NAVIGATION  ----------------------------------------------
// ----------------------------------------------------------------------------------------------------------
/**
 * Bascule entre le profil ou les boutons de connexion (selon si l'utilisateur est connecter ou non)
 * 
 * Retourne de l'HTML
 */
function ConnectForm(){
    $form = null;
    if(isset($_SESSION["IsConnected"]) && $_SESSION["IsConnected"] === true){
        $form .= "<div class=\"dropdown\">
                        <button class=\"dropdownStyle\">";
                        if(isset($_SESSION["StockedNickname"])){
                            $form .= $_SESSION["StockedNickname"];
                        }                        
                        $form .= "</button>
                        <div class=\"dropdown-child\">
                            <a href=\"profil.php\">Profil</a>
                            <a href=\"userLibrary.php\">Ma bibliothèque</a>";
                            if(isset($_SESSION["isAdmin"])){
                                $form .= "<a href=\"admin.php\">Gestionnaire de livres et critiques</a>";
                            }                           
                            $form .= "<a href=\"logout.php\">Déconnexion</a>
                        </div>
                    </div><br>";
    }
    else{
        $form .= "<button class=\"btnNav\"><a href=\"login.php\">Connexion</a></button><button class=\"btnNav\"><a href=\"register.php\">S'inscrire</a></button>";
    }
    return $form;
}

// ----------------------------------------------------------------------------------------------------------
// ------------------------------------ PAGE DESCRIPTIF (bookDetail.PHP) ------------------------------------
// ----------------------------------------------------------------------------------------------------------
/**
 * Récupère les informations d'un livre selon l'isbn situé dans l'url
 * 
 * Retourne un tableau
 */
function GetBookDetails(){
    $db = ConnectDB();
    $sql = $db->prepare("SELECT ROUND(AVG(`mark`), 1) AS mark, books.`isbn`, `title`, `author`, `editor`, `summary`, `editionDate`, `image` FROM books 
            JOIN reviews ON reviews.isbn = books.isbn
            WHERE books.isbn = :isbn");
    $sql->bindParam(':isbn', $_GET["id"]);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

/**
 * Mise en forme du tableau de données du livre
 * 
 * Retourne de l'HTML
 */
function BookDetailsForm(){
    $bookInfos = GetBookDetails();
    $desc = null;
    foreach ($bookInfos as $key => $value) {
        $desc .= <<<EX
            <div class="allDescContainer">
                <div class="imgBook">
                    <img src="img/{$value['image']}">
                </div>
                <div class="descContainer">
                    <h3>{$value['title']}</h3>
                    <p><b>Auteur : </b>{$value['author']}</p>
                    <p><b>Éditeur : </b>{$value['editor']}</p>
                    <p><b>ISBN : </b>{$value['isbn']}</p>
                    <p><b>Date d'édition : </b>{$value['editionDate']}</p>
EX;

                if(isset($_SESSION["IsConnected"])){
                    $desc .= <<<EX
                    <p><b>Note : </b>{$value['mark']}</p>
                    <form name="favBook" method="POST">
                        <label><b>Favori : </b></label>
                        <div class="fav">
                            <button value="{$value["isbn"]}" name="btnFavori">★</button>
                        </div>
                    </form>
EX;
                }
        $desc .= <<<EX
                </div>
                <div class="summary">
                    <b>Résumé : </b>
                    <p>{$value['summary']}</p>
                </div>
            </div>
EX;
    }
    return $desc;
}

/**
 * Affiche le formulaire pour ajouter une critique
 * 
 * Retourne un formulaire HTML
 */
function ShowReviewForm(){
    $reviewForm = null;    
    if(isset($_SESSION["savedReview"])){
        $savedReview = $_SESSION["savedReview"];
    }
    else{
        $savedReview = null;
    }
    $reviewForm .= '<form name="frmReview" method="POST">
                        <label>Critique : </label>
                        <label id="score">Donner une note : </label>
                        <textarea name="txtaReview" placeholder="Rédiger une critique">'.$savedReview.'</textarea>
                        <select name="scoreBook">
                            <option value="">--</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                        <input type="submit" name="btnPost" value="Poster">
                    </form>';
   return $reviewForm;
}

/**
 * Ajoute la critique et le score dans la base de données
 * 
 * Param : $review (critique de l'utilisateur)
 *         $score (note du livre donné par l'utilisateur)
 * 
 * Retourne true ou false
 */
function addReview($review, $score){
    $db = ConnectDB();
    $sql = $db->prepare("INSERT INTO reviews (`date`, `content`, `mark`, `isValid`, `isbn`, `pseudo`) VALUES (:dateNow, :review, :score, false, :isbn, :pseudo)");   
    try{
        $sql->execute(array(
            ':dateNow' => date("Y-m-d"),
            ':review' => $review,
            ':score' => $score,
            ':isbn' => $_GET['id'],
            ':pseudo' => $_SESSION["StockedNickname"],
        ));
    } catch (Exception $e) {
        return false;
    }
    return true;
}

/**
 * Récupère les critiques du livre validé par l'administrateur
 * 
 * Retourne un tableau
 */
function GetValidReviewOfBook(){
    $db = ConnectDB();
    $sql = $db->prepare('SELECT `date`, `content`, `mark`, `pseudo`, reviews.`isbn`, `title` FROM reviews 
        JOIN books
            ON books.isbn = reviews.isbn
        WHERE isValid = 1 AND reviews.isbn = :id ORDER BY idReview DESC');
        $sql->bindParam(':id', $_GET["id"]);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);   
    return $result;
}

/**
 * Affiche les critiques du livre récupéré par GetValidReview()
 * 
 * Retourne de l'HTML
 */
function ShowValidReviewOfBook(){
    $validReview = GetValidReviewOfBook();
    $review = null;
    foreach ($validReview as $key => $value) {
        $review .= <<<EX
        <div class="UserReview">
            <h3>{$value['pseudo']}</h3>
            <p>{$value["content"]}</p>
        </div>
EX;
    }
    return $review;
}

// ----------------------------------------------------------------------------------------------------------
// ---------------------------------------- PAGE PROFIL (profil.php) ----------------------------------------
// ----------------------------------------------------------------------------------------------------------
/**
 * Récupère les informations du compte connecté
 * 
 * Retourne un tableau
 */
function GetProfilDetails(){
    static $result = null;
    if($result == null){
        $db = ConnectDB();
        $sql = $db->prepare('SELECT `pseudo`, `email`, `password` FROM users WHERE `pseudo` = :Pseudo');
        $sql->bindParam(':Pseudo', $_SESSION["StockedNickname"], PDO::PARAM_STR);
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    }    
    return $result;
}

/**
 * Affiche les informations récupéré par la fonction GetProfilDetails()
 * 
 * Retourne de l'HTML
 */
function ProfilDetailsForm(){
    $profilDetails = GetProfilDetails();
    foreach ($profilDetails as $key => $value) {
        $infos = <<<EX
        <fieldset>
            <legend>Informations du compte</legend>           
            <form action="profil.php" method="POST">
                <input type="submit" class="inputUpdatePassword" name="btnUpdatePassword" value="Modifier le compte"><br>
EX;
            if(filter_has_var(INPUT_POST, "btnUpdatePassword") || filter_has_var(INPUT_POST, "btnConfirmUpdate")){
                if(!UpdatePasswordForm()){
                    $infos .= <<<EX
                        <label>Pseudo : </label><input type="text" name="tbxNewPseudo" value={$value["pseudo"]}><br>
                        <label>E-mail : </label><input type="email" name="tbxNewEmail" value={$value["email"]}><br>
                        <label>Ancien mot de passe : </label><input type="password" class="inputUpdatePassword" name="tbxOldPass" placeholder="Ancien mot de passe"><br>
                        <label>Nouveau mot de passe : </label><input type="password" class="inputUpdatePassword" name="tbxNewPass" placeholder="Nouveau mot de passe"><br>
                        <label id="info"><b>Pour une meilleur sécurité du mot de passe, mélangez : lettres, chiffres et caractère spéciaux !</b></label>
                        <label>Confirmer mot de passe : </label><input type="password" class="inputUpdatePassword" name="tbxConfirmNewPass" placeholder="Confirmer le nouveau mot de passe"><br>
                        <input type="submit" name="btnConfirmUpdate" value="Mettre à jour">
EX;
                }
            }
            else{
                $infos .= "<option>Pseudo : ".$value["pseudo"]."</option>
                <option>Email : ".$value["email"]."</option>";
            }            
        $infos .= "</form></fieldset>";
    }                   
    return $infos;
}

/**
 * Met à jour les informations (pseudo et email) dans la base de données
 */
function UpdateUser($newPseudo, $newEmail){
    $db = ConnectDB();
    $sql = $db->prepare("UPDATE users SET `pseudo` = :newPseudo, `email` = :newEmail WHERE pseudo = :Pseudo");
    $sql->bindParam(':newPseudo', $newPseudo, PDO::PARAM_STR);
    $sql->bindParam(':newEmail', $newEmail, PDO::PARAM_STR);
    $sql->bindParam(':Pseudo', $_SESSION["StockedNickname"], PDO::PARAM_STR);
    $sql->execute();
    $_SESSION["StockedNickname"] = $newPseudo;
}

/**
 * Met à jour le mot de passe dans la base de données
 */
function UpdatePassword($password){
    $db = ConnectDB();
    $sql = $db->prepare("UPDATE users SET `password` = :newPass WHERE pseudo = :Pseudo");
    $sql->bindParam(':newPass', $password, PDO::PARAM_STR);
    $sql->bindParam(':Pseudo', $_SESSION["StockedNickname"], PDO::PARAM_STR);   
    $sql->execute();
}

/**
 * Conditions pour la modification du mot de passe
 * 
 * Retourne true ou false
 */ 
function UpdatePasswordForm(){
    $profilInfos = GetProfilDetails();
    $salt = isset($_SESSION["salt"]);
    $pseudo = filter_input(INPUT_POST, "tbxNewPseudo");
    $email = filter_input(INPUT_POST, "tbxNewEmail");
    $oldPass = filter_input(INPUT_POST, "tbxOldPass");
    $newPass = filter_input(INPUT_POST, "tbxNewPass");
    $confirmNewPass = filter_input(INPUT_POST, "tbxConfirmNewPass");
    foreach ($profilInfos as $key => $value) {
        if(filter_has_var(INPUT_POST, "btnConfirmUpdate")){           
            if(!empty($oldPass) && !empty($newPass) && !empty($confirmNewPass) && !empty($pseudo) && !empty($email)){
                if(hash("sha256", $oldPass.$salt) == $value["password"]){
                    if(hash("sha256", $newPass.$salt) != hash("sha256", $oldPass.$salt)){
                        if(hash("sha256", $newPass.$salt) == hash("sha256", $confirmNewPass.$salt)){
                            UpdateUser($pseudo, $email);
                            UpdatePassword(hash("sha256", $newPass.$salt));
                            header("Location: profil.php");
                            return true;
                        }
                        else{
                            $_SESSION["msg"] = "<h4>Les mots de passe ne sont pas identique</h4>";
                            return false;                        
                        }
                    }
                    else{
                        $_SESSION["msg"] =  "<h4>Votre mot de passe est identique à l'ancien !</h4>";
                        return false;
                    }
                }
                else{
                    $_SESSION["msg"] =  "<h4>Mot de passe incorrect !</h4>";
                    return false;
                }
            }
            else{
                $_SESSION["msg"] =  "<h4>Veuillez remplir tout les champs !</h4>";
                return false;
            }
        }       
    }
}

/**
 * Récupère les critiques non validé par l'administrateur
 * 
 * Retourne un tableau
 */
function GetNotValidReview(){
    $db = ConnectDB();
    $sql = $db->prepare('SELECT `idReview`, `date`, `content`, `mark`, `pseudo`, reviews.`isbn`, `title` FROM reviews 
        JOIN books
            ON books.isbn = reviews.isbn
        WHERE isValid = 0 AND pseudo = :Pseudo');
        $sql->bindParam(':Pseudo', $_SESSION["StockedNickname"], PDO::PARAM_STR);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);   
    return $result;
}

/**
 * Affiche les critiques récupéré par GetNotValidReview()
 * 
 * Retourne de l'HTML
 */
function ShowNotValidReview(){
    $notValidReview = GetNotValidReview();
    $review = null;
    foreach ($notValidReview as $key => $value) {
        if(filter_has_var(INPUT_POST, "btnEdit") && filter_input(INPUT_POST, "btnEdit") == $value["idReview"]){
            $review .= <<<EX
            <div class="UserReview">
                <h3><a href="bookDetail.php?id={$value['isbn']}">{$value['title']}</a></h3>
                <form method="POST">
                    <textarea name="txtaNewReview">{$value["content"]}</textarea>
                    <select name="newScore">
                        <option value="">--</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                    <button type="submit" name="btnConfirmEdit" value={$value['idReview']}>Mettre à jour</button>
                </form>
            </div>         
EX;       
        }
        else{
            $review .= <<<EX
            <div class="UserReview">
                <h3><a href="bookDetail.php?id={$value['isbn']}">{$value['title']}</a></h3>
                <form method="POST">
                    <button name="btnEdit" value={$value['idReview']}>Modifier</button>
                    <button name="btnDelete" value={$value['idReview']}>Supprimer</button>
                </form>
                <p>{$value["content"]}</p>
            </div>
EX;
        }
    }
    return $review;
}

/**
 * Récupère les critiques validé par l'administrateur
 * 
 * Retourne un tableau
 */
function GetValidReview(){
    $db = ConnectDB();
    $sql = $db->prepare('SELECT `idReview`, `date`, `content`, `mark`, `pseudo`, reviews.`isbn`, `title` FROM reviews 
        JOIN books
            ON books.isbn = reviews.isbn
        WHERE isValid = 1 AND pseudo = :Pseudo');
        $sql->bindParam(':Pseudo', $_SESSION["StockedNickname"], PDO::PARAM_STR);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);   
    return $result;
}

/**
 * Affiche les critiques récupéré par GetValidReview()
 * 
 * Retourne de l'HTML
 */
function ShowValidReview(){
    $validReview = GetValidReview();
    $review = null;
    foreach ($validReview as $key => $value) {
        if(filter_has_var(INPUT_POST, "btnEdit") && filter_input(INPUT_POST, "btnEdit") == $value["idReview"]){
                $review .= <<<EX
                <div class="UserReview">
                    <h3><a href="bookDetail.php?id={$value['isbn']}">{$value['title']}</a></h3>
                    <form method="POST">
                        <textarea name="txtaNewReview">{$value["content"]}</textarea>
                        <select name="newScore">
                            <option value="">{$value['mark']}</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                        <button type="submit" name="btnConfirmEdit" value={$value['idReview']}>Mettre à jour</button>
                    </form>
                </div>              
EX;
        }
        else{
            $review .= <<<EX
        <div class="UserReview">
            <h3><a href="bookDetail.php?id={$value['isbn']}">{$value['title']}</a></h3>
            <form method="POST">
                <button name="btnEdit" value={$value['idReview']}>Modifier</button>
                <button name="btnDelete" value={$value['idReview']}>Supprimer</button>
            </form>
            <label class="reviewMark"><b>Note : </b>{$value['mark']}</label>
            <p>{$value["content"]}</p>
        </div>
EX;
        }       
    }
    return $review;
}

/**
 * Met à jour la critique
 */
function UpdateReview($newContent, $newMark, $id){
    $db = ConnectDB();
    $sql = $db->prepare("UPDATE reviews SET `content` = :newContent, `mark` = :newMark WHERE idReview = :id");
    $sql->bindParam(':newContent', $newContent, PDO::PARAM_STR);
    $sql->bindParam(':newMark', $newMark, PDO::PARAM_STR);
    $sql->bindParam(':id', $id, PDO::PARAM_STR);
    try{
        $sql->execute();
    } catch (Exception $e){
        echo 'INFOS REQUETE : ',  $e->getMessage(), "\n";
        exit();
    }
}

// ---------------------------------------------------------------------------------------------------------
// ----------------------------------- MA BIBLIOTHEQUE (userLibrary.php) -----------------------------------
// ---------------------------------------------------------------------------------------------------------
/**
 * Ajoute un lien entre l'utilisateur et le livre
 * 
 * Param : $pseudo (Pesudo de l'utilisateur)
 *         $idBook (ISBN du livre à ajouter)
 * 
 * Retourne true ou false
 */
function IsAlreadyInFavList($pseudo, $isbn){
    $db = ConnectDB();
    $sql = $db->prepare('SELECT `isbn`, `pseudo` FROM users_has_books WHERE isbn = :isbn AND pseudo = :Pseudo');
    $sql->bindParam(':isbn', $isbn, PDO::PARAM_STR);
    $sql->bindParam(':Pseudo', $_SESSION["StockedNickname"], PDO::PARAM_STR);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);   
    return $result;
}

/**
 * Ajoute un lien entre l'utilisateur et le livre
 * 
 * Param : $pseudo (Pesudo de l'utilisateur)
 *         $idBook (ISBN du livre à ajouter)
 * 
 * Retourne true ou false
 */
function AddToFavList($pseudo, $idBook){
    $db = ConnectDB();
    $sql = $db->prepare("INSERT INTO users_has_books (`pseudo`, `isbn`) VALUES (:pseudo, :id)");
    try{
        $sql->execute(array(
            ':pseudo' => $pseudo,
            ':id' => $idBook,
        ));
    } catch (Exception $e) {
        echo 'Insertion impossible : ',  $e->getMessage(), "\n";
        exit();
    }
    return true;
}

/**
 * Récupère les livres favori de l'utilisateur
 * 
 * Retourne un tableau
 */
function GetFavBooks(){
    $db = ConnectDB();
    // récupère les films favoris
    $sql = $db->prepare('(
        SELECT COUNT(idReview) AS nbReviews, ROUND(AVG(mark), 1) AS mark, reviews.isbn, title, author, editor, editionDate, image
        FROM reviews 
        JOIN books ON reviews.isbn = books.isbn
        JOIN users_has_books ON books.isbn = users_has_books.isbn
        WHERE users_has_books.pseudo = :Pseudo
        GROUP BY reviews.isbn
        )
        UNION
        (
        SELECT null, null AS mark, books.isbn, title, author, editor, editionDate, image
        FROM books
        JOIN users_has_books ON books.isbn = users_has_books.isbn
        WHERE books.isbn NOT IN (
            SELECT books.isbn FROM books
            JOIN reviews ON books.isbn = reviews.isbn
            GROUP BY reviews.isbn
        )
        AND users_has_books.pseudo = :Pseudo
        )');
    $sql->bindParam(':Pseudo', $_SESSION["StockedNickname"]);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

/**
 * Affiche les livres récupéré par GetFavBooks()
 * 
 * Retourne de l'HTML
 */
function ShowFavBooksForm(){
    $favBooks = GetFavBooks();
    $tab = null;
    if(empty($favBooks)){
        $_SESSION["msgEmpty"] = "<h4>Votre bibliothèque est vide !</h4>";
    }
    foreach($favBooks as $key => $value){       
        $tab .= <<<EX
                <div class="allBooksContainer">
                    <div class="bookContainer">
                        <div class="bookImg">
                            <img src="img/{$value['image']}"/>
                        </div>
                        <div class="bookTitle">                           
                            <strong>
                                <a name="link" href="bookDetail.php?id={$value['isbn']}">{$value['title']}</a>
                            </strong>
                        </div>
                        <div class="bookScoreFav">
                            <label>Auteur : {$value['author']}</label><br>
                            <label>Editeur : {$value['editor']}</label><br>
                            <label>Note : {$value['mark']}</label>
                            <form method="POST">
                                <button name="btnDeleteLink" value="{$value['isbn']}">Supprimer</button>
                            </form>
EX;
        $tab .= "</div></div></div>";
    }
    return $tab;
}

/**
 * Supprime le lien entre l'utilisateur et le livre
 */
function DeleteToFavList($id, $pseudo){
    try{
        $db = ConnectDB();
        $sql = $db->prepare("DELETE FROM users_has_books WHERE isbn = :Id AND pseudo = :Pseudo");
        $sql->bindParam(':Id', $id);
        $sql->bindParam(':Pseudo', $pseudo, PDO::PARAM_STR);
        $sql->execute();
    } catch (Exception $e){
        echo $e->getMessage();
    }
}

// ---------------------------------------------------------------------------------------------------------
// ------------------------------------ PAGE ADMINISTRATEUR (admin.php) ------------------------------------
// ---------------------------------------------------------------------------------------------------------
/**
 * Affiche le formulaire pour ajouter un nouveau livre
 * 
 * Retourne un formulaire HTML
 */
function AddBookForm(){
    $form = null;
    $form .= "<form action=\"admin.php\" method=\"POST\"  enctype=\"multipart/form-data\">
                <input type=\"submit\" class=\"inputInsertBook\" name=\"btnNewBook\" value=\"Nouveau livre\">";
        if(filter_has_var(INPUT_POST, "btnNewBook") || filter_has_var(INPUT_POST, "btnAddBook")){
            //if(addBook()){
                $form .= "
                    <input type=\"text\" class=\"inputInsertBook\" name=\"tbxTitle\" placeholder=\"Titre du livre\">
                    <input type=\"text\" class=\"inputInsertBook\" name=\"tbxAuthor\" placeholder=\"Auteur du livre\">
                    <input type=\"text\" class=\"inputInsertBook\" name=\"tbxEditor\" placeholder=\"Editeur du livre\">
                    <textarea class=\"inputInsertBook\" name=\"txtaSummary\" placeholder=\"Résumé du livre\"></textarea>                    
                    <input type=\"text\" class=\"inputInsertBook\" name=\"tbxIsbn\" placeholder=\"ISBN du livre\">
                    <input type=\"text\" class=\"inputInsertBook\" name=\"tbxEditionDate\" placeholder=\"Date d'édition\">
                    <input type=\"file\" class=\"inputInsertBook\" name=\"img[]\">
                    <input type=\"submit\" name=\"btnAddBook\" value=\"Ajouter le livre\">";
            //}               
        }
    $form .= "</form>";
    return $form;
}

/**
 * Ajoute un livre en base de données
 * 
 * Param : $title   (Titre du livre)
 *         $author  (Auteur du livre)
 *         $editor  (éditeur du livre)
 *         $summary (résumé du livre)
 *         $isbn    (code ISBN du livre)
 *         $editionDate (Date d'édition du livre)
 *         $img     image représantant le livre)
 * 
 * Retourne tru ou false
 */
function AddBook($title, $author, $editor, $summary, $isbn, $editionDate, $img){
    $db = ConnectDB();
    $sql = $db->prepare("INSERT INTO books (`title`, `author`, `editor`, `summary`, `isbn`, `editionDate`, `image`) VALUES (:Title, :Author, :Editor, :Summary, :Isbn, :EditionDate, :Img)");   
    try{
        $sql->execute(array(
            ':Title' => $title,
            ':Author' => $author,
            ':Editor' => $editor,
            ':Summary' => $summary,
            ':Isbn' => $isbn,
            ':EditionDate' => $editionDate,
            ':Img' => $img,
        ));
    } catch (Exception $e) {
        return false;
    }
    return true;
}

/**
 * Récupère les informations du livre à modifier
 * 
 * Retourne un tableau
 */
function GetInfosToUpdate(){
    $db = ConnectDB();
    $sql = $db->prepare('SELECT `isbn`, `title`, `author`, `editor`, `summary`, `editionDate` FROM books WHERE isbn = :Isbn');
    $sql->bindParam(':Isbn', $_SESSION["id"], PDO::PARAM_STR);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);   
    return $result;
}

/**
 * Affiche le formulaire pour modifier un livre
 * 
 * Retourne un formulaire HTML remplis
 */
function UpdateBookForm(){
    $infos = GetInfosToUpdate();
    $form = null;
    $form .= "<form action=\"admin.php\" method=\"POST\"  enctype=\"multipart/form-data\">";
    foreach ($infos as $key => $value) {
        $form .= <<<EX
            <input type="text" class="inputInsertBook" name="tbxNewTitle" placeholder="Titre du livre" value="{$value['title']}">
            <input type="text" class="inputInsertBook" name="tbxNewAuthor" placeholder="Auteur du livre" value="{$value['author']}">
            <input type="text" class="inputInsertBook" name="tbxNewEditor" placeholder="Editeur du livre" value="{$value['editor']}">
            <textarea class="inputInsertBook" name="txtaNewSummary" placeholder="Résumé du livre">{$value['summary']}</textarea>                    
            <input type="text" class="inputInsertBook" name="tbxSameIsbn" placeholder="ISBN du livre" value="{$value['isbn']}" readonly>
            <input type="text" class="inputInsertBook" name="tbxNewEditionDate" placeholder="Date d'édition" value="{$value['editionDate']}">                   
            <input type="submit" name="btnEditBook" value="Mettre à jour">
EX;
        
    }
    $form .= "</form>";
    return $form;
}

/**
 * Met à jour les informations du livre dans la base de données
 * 
 * Param : $title   (Titre du livre)
 *         $author  (Auteur du livre)
 *         $editor  (éditeur du livre)
 *         $summary (résumé du livre)
 *         $editionDate (Date d'édition du livre)7
 * 
 * Retourne true ou false
 */
function UpdateBook($title, $author, $editor, $summary, $isbn, $editionDate){
    $db = ConnectDB();
    $sql = $db->prepare("UPDATE books SET `title` = :NewTitle, `author` = :NewAuthor, `editor` = :NewEditor, `summary` = :NewSummary, `editionDate` = :NewEditionDate WHERE isbn = :Isbn");
    $sql->bindValue(':NewTitle', $title, PDO::PARAM_STR);
    $sql->bindValue(':NewAuthor', $author, PDO::PARAM_STR);
    $sql->bindValue(':NewEditor', $editor, PDO::PARAM_STR);
    $sql->bindValue(':NewSummary', $summary, PDO::PARAM_STR);
    $sql->bindValue(':Isbn', $isbn, PDO::PARAM_STR);
    $sql->bindValue(':NewEditionDate', $editionDate, PDO::PARAM_STR);
    try{
        $sql->execute();
    } 
    catch (Exception $e){
        return false;
    }
    return true;
}

/**
 * Supprime un livre
 * 
 * retourne un tableau
 */
function GetImage($id){
    $db = ConnectDB();
    $sql = $db->prepare('SELECT `image` FROM books WHERE isbn = :Isbn');
    $sql->bindValue(':Isbn', $id, PDO::PARAM_STR);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

/**
 * Supprime les critiques liées au livre
 * 
 * Param : $id (ID du livre à supprimer)
 * 
 * retourne true ou false
 */
function DeleteLinkedReview($id){
    $db = ConnectDB();
    $sql = $db->prepare("DELETE FROM reviews WHERE isbn = :id");
    $sql->bindValue(':id', $id, PDO::PARAM_STR);
    try{
        $sql->execute();
    }
    catch (Exception $e){
        echo "Problème : ".$e->getMessage();
        //return false;
    }
    return true;
}

/**
 * Supprime le livre de la bibliothèque de tout les utilisateurs
 * 
 * Param : $id (ID du livre à supprimer)
 * 
 * retourne true ou false
 */
function DeleteFavLink($id){
    $db = ConnectDB();
    $sql = $db->prepare("DELETE FROM users_has_books WHERE isbn = :id");
    $sql->bindValue(':id', $id, PDO::PARAM_STR);
    try{
        $sql->execute();
    }
    catch (Exception $e){
        echo "Problème : ".$e->getMessage();
        //return false;
    }
    return true;
}

/**
 * Supprime un livre
 * 
 * Param : $id (ID du livre à supprimer)
 * 
 * retourne true ou false
 */
function DeleteBook($id){
    $db = ConnectDB();
    $sql = $db->prepare("DELETE FROM books WHERE isbn = :id");
    $sql->bindValue(':id', $id, PDO::PARAM_STR);
    try{
        $sql->execute();
    }
    catch (Exception $e){
        echo "Problème : ".$e->getMessage();
        //return false;
    }
    return true;
}

/**
 * Sauvegarde le nom de l'image de l'utilisateur dans le dossier img/
 */
function MoveUpdatedFile(){
    $uploadFolder = 'img';
    // Déplace le ou les fichiers dans un dossier
    foreach ($_FILES["img"]["error"] as $key => $error) {
        if ($error == UPLOAD_ERR_OK) {
            $tmp_name = $_FILES["img"]["tmp_name"][$key];
            $name = basename($_FILES["img"]["name"][$key]);               
            move_uploaded_file($tmp_name, "$uploadFolder/$name");
        }
    }
}

/**
 * Récupère toutes les critiques pas valider
 * 
 * Retourne un tableau
 */
function GetAllNotValidReview(){
    $db = ConnectDB();
    $sql = $db->prepare('SELECT `idReview`, `date`, `content`, `mark`, `pseudo`, reviews.`isbn`, `title` FROM reviews 
        JOIN books
            ON books.isbn = reviews.isbn
        WHERE isValid = 0');
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

/**
 * Affiche les critiques récupéré par GetAllNotValidReview()
 * 
 * Retourne de l'HTML et un formulaire avec deux boutons
 */
function ShowAllNotValidReview(){
    $notValidReview = GetAllNotValidReview();
    $review = null;
    foreach ($notValidReview as $key => $value) {
        $review .= <<<EX
        <div class="UserReview">
            <h3><a href="bookDetail.php?id={$value['isbn']}">{$value['title']}</a></h3>
            <form name="btnChoose" method="POST">
                <button name="btnValid" value={$value['idReview']}>Valider</button>
                <button name="btnUnvalid" value={$value['idReview']}>Refuser</button>
            </form>
            <strong>{$value['pseudo']}</strong>
            <p>{$value["content"]}</p>
        </div>
EX;
    }
    return $review;
}

/**
 * Met à jour la validité de la critique
 * 
 * Param : $id (ID de la critique)
 */
function UpdateToValid($id){
    $db = ConnectDB();
    $sql = $db->prepare("UPDATE reviews SET `isValid` = 1 WHERE idReview = :id");
    $sql->bindValue(':id', $id, PDO::PARAM_STR);
    $sql->execute();
}

/**
 * Supprime la critique
 * 
 * Param : $id (ID de la critique)
 */
function DeleteReview($id){
    $db = ConnectDB();
    $sql = $db->prepare("DELETE FROM reviews WHERE idReview = :id");
    $sql->bindValue(':id', $id, PDO::PARAM_STR);   
    $sql->execute();
}

// -----------------------------------------------------------------------------------------------------------
// -------------------------------- CONDITIONS BOUTONS (Page avec formulaire) --------------------------------
// -----------------------------------------------------------------------------------------------------------

// ===== Connexion =====
if(filter_has_var(INPUT_POST, 'btnLogin')){
    $nickname = filter_input(INPUT_POST, "tbxLoginNickname");
    $password = filter_input(INPUT_POST, "tbxLoginPassword");
    if(!empty($nickname) && !empty($password)){
        $hashPassword = hash("sha256", $password.$salt);
        if(LoginUser($nickname, $hashPassword)){
            header("Location: index.php");
        }
        else{
            $_SESSION["msg"] = "Pseudo ou mot de passe incorrect";
        }
    }
    else{
        $_SESSION["msg"] = "Veuillez compléter tous les champs";
    }
}

// ===== Enregistrement =====
if(filter_has_var(INPUT_POST, 'btnRegister')){
    $nickname = filter_input(INPUT_POST, "tbxRegisterNickname");
    $email = filter_input(INPUT_POST, "tbxRegisterEmail");
    $password = filter_input(INPUT_POST, "tbxRegisterPassword");
    $confirmPassword = filter_input(INPUT_POST, "tbxRegisterConfirmPassword");

    if(!empty($nickname) && !empty($email) && !empty($password)){
        $hachedPass = hash("sha256", $password.$salt);
        $hachedConfirmPass = hash("sha256", $confirmPassword.$salt);
        if($hachedPass == $hachedConfirmPass){
            if(InsertUser($nickname, $email, $hachedPass)){
                $_SESSION["salt"] = $salt;
                $_SESSION["msg"] = "Votre compte à bien été enregistré";
            }
            else{
                $_SESSION["msg"] = "Un problème est survenu, veuillez réessayer";
            }
        }
        else{
            $_SESSION["msg"] = "Les mots de passe ne sont pas identique";
        }
    }
    else{
        $_SESSION["msg"] = "Veuillez compléter tous les champs";
    }
}

// ===== Ajout note du livre et critique =====
if(filter_has_var(INPUT_POST, 'btnPost')){
    $review = filter_input(INPUT_POST, "txtaReview");
    $score = filter_input(INPUT_POST, "scoreBook");
    if($review != "" && $score != ""){
        if(addReview($review, $score)){
            $_SESSION["savedReview"] == null;
            $_SESSION["msgAddReview"] = "<h4>Critique envoyer à l'administrateur</h4>";
        }
        else{
            $_SESSION["msgAddReview"] = "<h4>Un problème est survenu, veuillez réessayer</h4>";
        }
    }
    else{
        $_SESSION["savedReview"] = $review;
        $_SESSION["msgAddReview"] = "<h4>Veuillez compléter tous les champs</h4>";
    }
}

// ===== Validation de la critique par l'administrateur =====
if(filter_has_var(INPUT_POST, "btnValid")){
    $id = filter_input(INPUT_POST, "btnValid");
    UpdateToValid($id);
}

// ===== Refus de la critique par l'administrateur =====
if(filter_has_var(INPUT_POST, "btnUnvalid")){
    $id = filter_input(INPUT_POST, "btnUnvalid");
    DeleteReview($id);
}

// ===== Modification de la critique par l'utilisateur =====
if(filter_has_var(INPUT_POST, "btnConfirmEdit")){
    $id = filter_input(INPUT_POST, "btnConfirmEdit");
    $newReview = filter_input(INPUT_POST, "txtaNewReview");
    $newScore = filter_input(INPUT_POST, "newScore");
    if(empty($newReview) || $newScore == ""){
        $_SESSION["msgEditReview"] = "<h4>Veuillez compléter tous les champs</h4>";
    }
    else{       
        UpdateReview($newReview, $newScore, $id);
    }
}

// ===== Suppression de la critique par l'utilisateur =====
if(filter_has_var(INPUT_POST, "btnDelete")){
    $id = filter_input(INPUT_POST, "btnDelete");
    DeleteReview($id);
}

// ===== Recherche =====
if(filter_has_var(INPUT_POST, "btnSearch")){
    $searchTitle = filter_input(INPUT_POST, "tbxSearchTitle");
    $searchAuthor = filter_input(INPUT_POST, "tbxSearchAuthor");
    $searchEditor = filter_input(INPUT_POST, "tbxSearchEditor");
    $_SESSION["searchTitle"] = "%".$searchTitle."%";
    $_SESSION["searchAuthor"] = "%".$searchAuthor."%";
    $_SESSION["searchEditor"] = "%".$searchEditor."%";
    FindedForm();
}

// ===== Trie par ordre alphabétique =====
if(filter_has_var(INPUT_POST, "sortBooks")){
    $_SESSION["search"] = null;
    $_SESSION["sortBooks"] = null;
    $sort = filter_input(INPUT_POST, "sortBooks", FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
    $_SESSION["sortBooks"] = $sort;
}

// ===== Annule les filtres =====
if(filter_has_var(INPUT_POST, "btnResetFilter")){
    $_SESSION["searchTitle"] = null;
    $_SESSION["searchAuthor"] = null;
    $_SESSION["searchEditor"] = null;
    $_SESSION["sortBooks"] = null;
}

// ===== Ajoute d'un livre dans la bibliothèque =====
if(filter_has_var(INPUT_POST, "btnFavori")){
    $favBook = $_POST["btnFavori"];
    $pseudo = $_SESSION["StockedNickname"];
    if(!IsAlreadyInFavList($pseudo, $favBook)){
        if(AddToFavList($pseudo, $favBook)){
            $_SESSION["msgFav"] = "<h4>Le livre a été ajouté à votre bibliothèque !</h4>";
        }
    }
    else{
        $_SESSION["msgFav"] = "<h4>Ce livre est déjà dans votre bibliothèque !</h4>";
    }
}

// ===== Suppression d'un livre de la bibliothèque =====
if(filter_has_var(INPUT_POST, "btnDeleteLink")){
    $favBook = $_POST["btnDeleteLink"];
    $pseudo = $_SESSION["StockedNickname"];
    DeleteToFavList($favBook, $pseudo);
}

// ===== Ajout d'un livre dans le site (admin) =====
if(filter_has_var(INPUT_POST, "btnAddBook")){
    $title = filter_input(INPUT_POST, "tbxTitle");
    $author = filter_input(INPUT_POST, "tbxAuthor");
    $editor = filter_input(INPUT_POST, "tbxEditor");
    $summary = filter_input(INPUT_POST, "txtaSummary");
    $isbn = filter_input(INPUT_POST, "tbxIsbn");
    $editionDate = filter_input(INPUT_POST, "tbxEditionDate");
    $img = $_FILES['img']['name'][0];    
    if(!empty($title) && !empty($author) && !empty($editor) && !empty($summary) && !empty($isbn) && !empty($editionDate) && $_FILES['img']['name'][0] != ""){
        if(AddBook($title, $author, $editor, $summary, $isbn, $editionDate, $img)){
            MoveUpdatedFile();
            $_SESSION["msgAddBook"] = "<h4>Le livre a été ajouté !</h4>";
        }
        else{
            $_SESSION["msgAddBook"] = "<h4>Un problème est survenu, Veillez à ce que les champs soient tous remplis !</h4>";
        }
    }
    else{
        $_SESSION["msgAddBook"] = "<h4>Le formulaire est incomplet !</h4>";
    }
}

// ===== Modification d'un livre dans le site (admin) =====
if(filter_has_var(INPUT_POST, "btnAdminEdit")){
    $id = filter_input(INPUT_POST, "btnAdminEdit");
    $_SESSION["id"] = $id;
    $_SESSION["adminEdit"] = true;
    header("Location: admin.php");
}

// ===== Met à jour les informations du livre (admin) =====
if(filter_has_var(INPUT_POST, "btnEditBook")){
    $newTitle = filter_input(INPUT_POST, "tbxNewTitle");
    $newAuthor = filter_input(INPUT_POST, "tbxNewAuthor");
    $newEditor = filter_input(INPUT_POST, "tbxNewEditor");
    $newSummary = filter_input(INPUT_POST, "txtaNewSummary");
    $isbn = filter_input(INPUT_POST, "tbxSameIsbn");
    $newEditionDate = filter_input(INPUT_POST, "tbxNewEditionDate");
    $_SESSION["adminEdit"] = null;
    if(UpdateBook($newTitle, $newAuthor, $newEditor, $newSummary, $isbn, $newEditionDate)){
        $_SESSION["msgUpdateBook"] = "<h4>Le livre a été mis à jour !</h4>";
    }
    else{
        $_SESSION["msgUpdateBook"] = "<h4>Un problème est survenu lors de la modification. Veuillez réessayer</h4>";
    }
}

// ===== Suppression d'un livre dans le site (admin) =====
if(filter_has_var(INPUT_POST, "btnAdminDelete")){
    $id = filter_input(INPUT_POST, "btnAdminDelete");
    $imgToDelete = GetImage($id);
    foreach ($imgToDelete as $key => $value) {
        $img = $value["image"];
    }
    if(DeleteLinkedReview($id)){
        if(DeleteFavLink($id)){
            if(DeleteBook($id)){
                unlink('img/'.$img);
                $_SESSION["msgDeleteBook"] = "<h4>La suppression du livre c'est bien passé !</h4>";
            }
            else{
                $_SESSION["msgDeleteBook"] = "<h4>Un problème est survenu !</h4>";
            }   
        }
        else{
            $_SESSION["msgDeleteBook"] = "<h4>Un problème est survenu !</h4>";
        }
    }
    else{       
        $_SESSION["msgDeleteBook"] = "<h4>Un problème est survenu !</h4>";
    }    
}