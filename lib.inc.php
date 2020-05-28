<?php
if(session_status() == PHP_SESSION_NONE){
    session_start(); 
}
$_SESSION["msg"] = "";

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
            $db = new PDO('mysql:host=localhost;dbname=dbtpi;port=3306','adminTpi','tpi2020');
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
    $sql = $db->prepare("SELECT `pseudo`, `password` FROM users WHERE pseudo = :Pseudo");    
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
        $sql = $db->prepare("SELECT `isbn`, `title`, `author`, `editor`, `summary`, `editionDate`, `image` FROM books ORDER BY title ASC");
    }
    // Filtre par ordre alphabétique - Auteur
    else if(isset($_SESSION["sortBooks"]) && $_SESSION["sortBooks"] == "Auteur"){
        $sql = $db->prepare('SELECT `isbn`, `title`, `author`, `editor`, `summary`, `editionDate`, `image` FROM books ORDER BY author ASC');
    }
    // Filtre par ordre alphabétique - Editeur
    else if(isset($_SESSION["sortBooks"]) && $_SESSION["sortBooks"] == "Editeur"){
        $sql = $db->prepare('SELECT `isbn`, `title`, `author`, `editor`, `summary`, `editionDate`, `image` FROM books ORDER BY editor ASC');
    }
    // Sans filtre
    else{
        $sql = $db->prepare("SELECT `isbn`, `title`, `author`, `editor`, `summary`, `editionDate`, `image` FROM books");
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
                                <a href="bookDetail.php?id={$value['isbn']}">{$value['title']}</a>
                            </strong>
                        </div>
                        <div class="bookScoreFav">
                            <label>Note : Chercher note</label>
EX;

            if(isset($_SESSION["IsConnected"])){
                $tab .= <<<EX
                <form method="POST">
                    <button value="{$value["isbn"]}" name="btnFavori">★</button>
                </form>
EX;
            }
            $tab .= "</div></div></div>";
        }
    }   
    return $tab;
}

function Search(){
    $db = ConnectDB();
    $sql = $db->prepare('SELECT * FROM books WHERE title LIKE concat("%", :searching, "%") OR author LIKE concat("%", :searching, "%") OR editor LIKE concat("%", :searching, "%")');
    $sql->bindParam(':searching', $_SESSION["search"]);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

function FindedForm(){
    $finded = Search();
    $tab = null;
    foreach($finded as $key => $value){            
        $tab .= <<<EX
            <div class="allBooksContainer">
                <div class="bookContainer">
                    <div class="bookImg">
                        <img src="img/{$value['image']}"/>
                    </div>
                    <div class="bookTitle">
                        <strong>
                            <a href="bookDetail.php?id={$value['isbn']}">{$value['title']}</a>
                        </strong>
                    </div>
                    <div class="bookScoreFav">
                        <label>Note : Chercher note</label>
EX;

        if(isset($_SESSION["IsConnected"])){
            $tab .= <<<EX
            <form method="POST">
                <button value="{$value["isbn"]}" name="btnFavori">★</button>
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
                            <a href=\"favoris.php\">Ma bibliothèque</a>
                            <a href=\"logout.php\">Deconnexion</a>
                        </div>
                    </div><br>";
    }
    else{
        $form .= "<button><a href=\"login.php\">Connexion</a></button><button><a href=\"register.php\">S'inscrire</a></button>";
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
    $sql = $db->prepare("SELECT `isbn`, `title`, `author`, `editor`, `summary`, `editionDate`, `image` FROM books WHERE isbn = :isbn");
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
                    <p><b>Date d'édition : </b>{$value['editionDate']}</p>                   
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
    $reviewForm .= '<form method="POST">
                        <label>Critique</label>
                        <textarea name="txtaReview" placeholder="Rédiger une critique"></textarea>
                        <label>Donner une note : </label>
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
        WHERE isValid = 1 AND reviews.isbn = :id');
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
        $infos = "
        <fieldset>
            <legend>Informations du compte</legend>
            <option>Pseudo : ".$value["pseudo"]."</option>
            <option>Email : ".$value["email"]."</option>
            <form action=\"profil.php\" method=\"POST\">
                <input type=\"submit\" class=\"inputUpdatePassword\" name=\"btnUpdatePassword\" value=\"Modifier le mot de passe\"><br>";
            if(filter_has_var(INPUT_POST, "btnUpdatePassword") || filter_has_var(INPUT_POST, "btnConfirmUpdate")){
                if(!UpdatePasswordForm()){
                    $infos .= "                    
                        <input type=\"password\" class=\"inputUpdatePassword\" name=\"tbxOldPass\" placeholder=\"Ancien mot de passe\"><br>
                        <input type=\"password\" class=\"inputUpdatePassword\" name=\"tbxNewPass\" placeholder=\"Nouveau mot de passe\"><br>
                        <input type=\"password\" class=\"inputUpdatePassword\" name=\"tbxConfirmNewPass\" placeholder=\"Confirmer le nouveau mot de passe\">                
                        <input type=\"submit\" name=\"btnConfirmUpdate\">";
                }
            }
        $infos .= "</form></fieldset>";
    }                   
    return $infos;
}

/**
 * Met à jour le mot de passe dans la base de données
 */
function UpdatePassword($password){
    $db = ConnectDB();
    $sql = $db->prepare("UPDATE users SET `password` = :newPass WHERE pseudo = :Pseudo");
    $sql->bindValue(':newPass', $password, PDO::PARAM_STR);
    $sql->bindValue(':Pseudo', $_SESSION["StockedNickname"], PDO::PARAM_STR);    
    $sql->execute();
}

/**
 * Conditions pour la modification du mot de passe
 * 
 * Retourne true ou false
 */ 
function UpdatePasswordForm(){   
    $profilInfos = GetProfilDetails();
    $oldPass = filter_input(INPUT_POST, "tbxOldPass");
    $newPass = filter_input(INPUT_POST, "tbxNewPass");
    $confirmNewPass = filter_input(INPUT_POST, "tbxConfirmNewPass");        
    foreach ($profilInfos as $key => $value) {
        if(filter_has_var(INPUT_POST, "btnConfirmUpdate")){
            if(!empty($oldPass) && !empty($newPass) && !empty($confirmNewPass)){
                if(hash("sha256", $oldPass) == $value["password"]){
                    if(hash("sha256", $newPass) != hash("sha256", $oldPass)){
                        if(hash("sha256", $newPass) == hash("sha256", $confirmNewPass)){
                            $_SESSION["msg"] =  "Votre mot de passe à bien été changé !";
                            UpdatePassword(hash("sha256", $newPass));
                            return true;
                        }
                        else{
                            $_SESSION["msg"] = "Les mots de passe ne sont pas identique";
                            return false;                        
                        }
                    }
                    else{
                        $_SESSION["msg"] =  "Votre mot de passe est identique à l'ancien !";
                        return false;
                    }
                }
                else{
                    $_SESSION["msg"] =  "Mot de passe incorrect !";
                    return false;
                }
            }
            else{
                $_SESSION["msg"] =  "Veuillez remplir tout les champs !";
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
        WHERE isValid = 0');
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
        WHERE isValid = 1');
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
        if(isset($_SESSION["Edit"]) && $_SESSION["Edit"] == true){
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
                    <input type="submit" name="btnConfirmEdit" value="Mettre à jour">
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
 * Met à jour la critique
 */
function UpdateReview($newContent, $newMark, $id){
    $db = ConnectDB();
    $sql = $db->prepare("UPDATE reviews SET `content` = :newContent, `mark` = :newMark WHERE idReview = :id");
    $sql->bindValue(':newContent', $newContent, PDO::PARAM_STR);
    $sql->bindValue(':newMark', $newMark, PDO::PARAM_STR);
    $sql->bindValue(':id', $id, PDO::PARAM_STR);    
    $sql->execute();
}

// ---------------------------------------------------------------------------------------------------------
// -------------------------------------- PAGE PRINCIPALE (admin.php) --------------------------------------
// ---------------------------------------------------------------------------------------------------------
/**
 * Affiche le formulaire pour ajouter un nouveau livre
 * 
 * Retourne un formulaire HTML
 */
function AddBookForm(){
    $form = null;
    $form .= "<form action=\"admin.php\" method=\"POST\">
                <input type=\"submit\" class=\"inputInsertBook\" name=\"btnNewBook\" value=\"Nouveau livre\">";
        if(filter_has_var(INPUT_POST, "btnNewBook") || filter_has_var(INPUT_POST, "btnAddBook")){
            //if(addBook()){
                $form .= "
                    <input type=\"text\" class=\"inputInsertBook\" name=\"tbxTitle\" placeholder=\"Titre du livre\">
                    <input type=\"text\" class=\"inputInsertBook\" name=\"tbxAuthor\" placeholder=\"Auteur du livre\">
                    <input type=\"text\" class=\"inputInsertBook\" name=\"tbxEditor\" placeholder=\"Editeur du livre\">
                    <textarea class=\"inputInsertBook\" name=\"tbxSummary\" placeholder=\"Résumé du livre\"></textarea>                    
                    <input type=\"text\" class=\"inputInsertBook\" name=\"tbxIsbn\" placeholder=\"ISBN du livre\">
                    <input type=\"text\" class=\"inputInsertBook\" name=\"tbxEditionDate\" placeholder=\"Date d'édition\">
                    <input type=\"file\" class=\"inputInsertBook\" name=\"img[]\">
                    <input type=\"submit\" name=\"btnAddBook\">";
            //}               
        }
    $form .= "</form>";
    return $form;
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
            <h3><a href="bookDetail.php?id={$value['isbn']}">{$value['title']}</a></h3>{$value['pseudo']}
            <form method="POST">
                <button name="btnValid" value={$value['idReview']}>Valider</button>
                <button name="btnUnvalid" value={$value['idReview']}>Refuser</button>
            </form>
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
        $hashPassword = hash("sha256", $password);
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
        $hachedPass = hash("sha256", $password);
        $hachedConfirmPass = hash("sha256", $confirmPassword);
        if($hachedPass == $hachedConfirmPass){
            if(InsertUser($nickname, $email, $hachedPass)){           
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

// ===== Note du livre et critique =====
if(filter_has_var(INPUT_POST, 'btnPost')){
    $review = filter_input(INPUT_POST, "txtaReview");
    $score = filter_input(INPUT_POST, "scoreBook");
    if($review != "" && $score != ""){
        if(addReview($review, $score)){
            echo "Critique envoyer à l'administrateur";
        }
        else{
            echo "Un problème est survenu, veuillez réessayer";
        }
    }
    else{
        echo "Veuillez compléter tous les champs";
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
if(filter_has_var(INPUT_POST, "btnEdit")){
    $id = filter_input(INPUT_POST, "btnEdit");
    $_SESSION["Edit"] = true;
    if(filter_has_var(INPUT_POST, "btnConfirmEdit")){
        $newReview = filter_input(INPUT_POST, "txtaNewReview");
        $newScore = filter_input(INPUT_POST, "newScore");
        $_SESSION["Edit"] = null;
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
    $search = filter_input(INPUT_POST, "tbxSearch");
    $_SESSION["search"] = $search;
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
    $_SESSION["search"] = null;
    $_SESSION["sortBooks"] = null;
}