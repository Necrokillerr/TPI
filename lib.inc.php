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
 */
function LoginUser($nickname, $password){
    $db = ConnectDB();
    $sql = $db->prepare("SELECT Nickname, Pass FROM users WHERE Nickname = :Nickname");    
    try{
        $sql->execute(array(
        ':Nickname' => $nickname,
        ));
    } catch(Exception $e) {
        echo 'Connexion impossible : ',  $e->getMessage(), "\n";   
        exit();     
    }
    $result = $sql->fetch();
    if($password == $result[1]){
        $_SESSION["StockedNickname"] = $nickname;
        $_SESSION["IsConnected"] = true;
        return true;
    }
    else{
        return false;
    }
}

/**
 * Insertion d'un nouveau user
 */
function InsertUser($nickname, $email, $password){
    $db = ConnectDB();
    $sql = $db->prepare("INSERT INTO users (`Nickname`, `Email`, `Pass`, `registeredDate`) VALUES (:Nickname, :Email, :Pwd, :regDate)");   
    try{
        $sql->execute(array(
            ':Nickname' => $nickname,
            ':Email' => $email,
            ':Pwd' => $password,
            ':regDate' => date("Y-m-d H:i:s"),
        ));
    } catch (Exception $e) {
        echo 'Insertion impossible : ',  $e->getMessage(), "\n";
        exit();
    }
    return true;
}

// ---------------------------------------------------------------------------------------------------------
// -------------------------------------- PAGE PRINCIPALE (index.php) --------------------------------------
// ---------------------------------------------------------------------------------------------------------

function GetAllBooks(){
    $db = ConnectDB();
    // Filtre par ordre alphabétique
/*  if(isset($_SESSION["sortMovies"]) && $_SESSION["sortMovies"] == "ABC"){
        $sql = $db->prepare("SELECT idmovies, movLocalLink, movName FROM movies ORDER BY movName ASC");
    }
    // Filtre par les mieux notés
    else if(isset($_SESSION["sortScore"]) && $_SESSION["sortScore"] == "SCORE"){
        $sql = $db->prepare("SELECT movies.idmovies, movLocalLink, movName, Note FROM movies 
            JOIN usershasnote 
                ON movies.idmovies = usershasnote.idmovies
            ORDER BY Note DESC;");
    }
    // Filtre par genre
    else if(isset($_SESSION["sortMovies"]) && $_SESSION["sortMovies"] != null){
        $sql = $db->prepare('SELECT movies.idmovies, movLocalLink, movName FROM `movies`
            JOIN `movieshasgenre`
                ON movies.idmovies = movieshasgenre.idmovies
            JOIN `genre`
                ON movieshasgenre.idgenre = genre.idgenre
            WHERE genre.genre = "'.$_SESSION["sortMovies"].'"');
    }
    // Sans filtre
    else{
*/
        $sql = $db->prepare("SELECT `isbn`, `title`, `author`, `editor`, `summary`, `editionDate`, `image` FROM books");
    //}  
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

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
                    <button value="{$value["idmovies"]}" name="btnFavori">★</button>
                </form>
EX;
            }
            $tab .= "</div></div></div>";
        }
    }   
    return $tab;
}

// ----------------------------------------------------------------------------------------------------------
// ------------------------------------ PAGE DESCRIPTIF (bookDetail.PHP) ------------------------------------
// ----------------------------------------------------------------------------------------------------------

// Récupère les informations d'un livre selon l'isbn situé dans l'url
function GetBookDetails(){
    $db = ConnectDB();
    $sql = $db->prepare("SELECT `isbn`, `title`, `author`, `editor`, `summary`, `editionDate`, `image` FROM books WHERE isbn = :isbn");
    $sql->bindParam(':isbn', $_GET["id"]);
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

// Mise en forme du tableau de données du film
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
                    <p><b>Auteur</b> : {$value['author']}</p>
                    <p><b>Éditeur</b> : {$value['editor']}</p>
                    <p><b>Date d'édition</b> : {$value['editionDate']}</p>                   
                </div>
                <div class="summary">
                    <p>{$value['summary']}</p>
                </div>
            </div>
EX;
    }
    return $desc;
}

// ---------------------------------------------------------------------------------------------------------
// -------------------------------------- PAGE PRINCIPALE (admin.php) --------------------------------------
// ---------------------------------------------------------------------------------------------------------
function addBookForm(){
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

// -----------------------------------------------------------------------------------------------------------
// -------------------------------- CONDITIONS BOUTONS (Page avec formulaire) --------------------------------
// -----------------------------------------------------------------------------------------------------------

// ===== Connexion =====
if(filter_has_var(INPUT_POST, 'btnLogin')){
    $nickname = filter_input(INPUT_POST, "tbxLoginNickname");
    $password = hash("sha256", filter_input(INPUT_POST, "tbxLoginPassword"));
    if(!empty($nickname) && !empty($password)){
        if(LoginUser($nickname, $password)){                
            header("Location: index.php");
        }
        else{
            $_SESSION["msg"] = "Pseudo ou mot de passe incorrect";
        }
    }
    else{
        $_SESSION["msg"] = "Veuillez compléter tout les champs";
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
                $_SESSION["msg"] = "Un problème est survenu, veuillez rééssayer";
            }
        }
        else{
            $_SESSION["msg"] = "Les mots de passe ne sont pas identique";
        }
    }
    else{
        $_SESSION["msg"] = "Veuillez compléter tout les champs";
    }
}