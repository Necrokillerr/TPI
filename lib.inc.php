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