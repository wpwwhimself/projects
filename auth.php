<?php
session_start();
require("cue.php");
giveMeTheCue(0);

$lang = ($_GET['l'] == "en") ? true : false;
generateHead(($lang)?"Authentification":"Logowanie", 0, $lang);

if(isset($_POST['login']) || isset($_GET['b'])){
    //logowanie automatyczne na mocy pobłogosławionego linku do sejfu
    $flag = substr($_POST['login'],0,1);
    
    //sprawdzam, który "zaloguj" kliknięto po pierwszej literze etykiety przycisku
    if($flag == "B" || $flag == "P"){ //jeśli incog
        $_SESSION['whoami'] = "incognito";
        $_SESSION['clientid'] = null;
    }else{ //jeśli klient
        $password = (isset($_GET['b'])) ? $_GET['b'] : $_POST['loginpass'];
        $q = "SELECT nazwisko, id FROM p_klienci WHERE hasło = \"$password\"";
        echo $q;
        $r = $conn->query($q) or die($conn->error);
        $a = $r->fetch_assoc();
        if($a == "") $a = false;
            else{
                $_SESSION['whoami'] = $a['nazwisko'];
                $_SESSION['clientid'] = $a['id'];
            }
        $r->free_result();
    }
}

if($a !== false && isset($_SESSION['whoami'])){
    if(isset($_SESSION['wherewasi']) && $_SESSION['wherewasi'] != "/?logout=1") header("Location: ".$_SESSION['wherewasi']);
        else header("Location: http://projects.wpww.pl/");
    
    die();
}

?>

<div class="home">
<h1><?php echo ($lang) ? "Project list" : "Lista projektów"; ?></h1>

<form method="post" id="loginbox" class="flexright" style="align-items: center;">
    <section class="flexdown">
        <input type="password" id="loginpass" name="loginpass" placeholder="<?php echo ($lang)? "Type in your password" : "Podaj hasło"; ?>"></input>
        <p class="errortext"><?php if($a === false) echo ($lang) ? "Password is incorrect. Try again.":"Hasło się nie zgadza. Spróbuj ponownie."; ?></p>
        <input type="submit" name="login" value="<?php echo ($lang)? "Log in": "Zaloguj się"; ?>"></input>
    </section>
    <p><?php echo ($lang)? "or":"lub"; ?></p>
    <section>
        <input type="submit" name="login" value="<?php echo ($lang)? "Browse as a guest": "Przeglądaj jako gość"; ?>"></input>
    </section>
</form>

</div>

<div class="details">

</div>

<?
generateBottom("main");
?>