<?php
session_start();
$z = $_SERVER['DOCUMENT_ROOT']; $z .= "/cue.php"; require($z); //podpięcie generatora
giveMeTheCue(0); //łączenie z bazą danych

generateHead("Mailo-kurier");
#####

//betoniarka zmiennych
$q = "SELECT p.tytuł, k.nazwisko, k.kontakt, k.t_kontakt, k.hasło, k.contactpreference
    FROM p_projekty p
    LEFT JOIN p_klienci k ON p.inspiracja = k.id
    WHERE p.id = '".$_GET['id']."'";
$r = $conn->query($q) or die($conn->error);
$a = $r->fetch_assoc();
$r->free_result;

$odbiorca = prepareName($a['nazwisko']);

//czy tandem
$tandem = (strpos($a['nazwisko'], "/") !== false) ?
    array(substr($a['nazwisko'], 0, strpos($a['nazwisko'], "/")),
            substr($a['nazwisko'], strpos($a['nazwisko'], "/")+1))
    : false;

?>
<h1>Kurier</h1>
<p>Wyślij maila a propos projektu</p>

<?php

if(isset($_POST['m_sub'])){

if($_POST['m_bothguys']){ //tandem nie odmienia
    $mmhnrf = "Szanowni Państwo";
    $mmydop = "Państwa";
    $mmybrn = "Państwa";
}else{
    //adresat i płeć
    if($_POST['m_sexcorr']){
        $mmhnrf = ($_POST['m_sex']) ? "Szanowna Pani " : "Szanowny Panie ";
        $mmydop = ($_POST['m_sex']) ? "Pani" : "Pana";
        $mmybrn= ($_POST['m_sex']) ? "Panią" : "Pana";
    }
    else{ //gdyby endżin się pomylił z założeniem płci
        $mmhnrf = ($_POST['m_sex']) ? "Szanowny Panie " : "Szanowna Pani ";
        $mmydop = ($_POST['m_sex']) ? "Pana" : "Pani";
        $mmybrn = ($_POST['m_sex']) ? "Pana" : "Panią";
    }
    $mmhnrf .= $_POST['m_who'];
}

//zbierz wszystkie projekty
$prid = explode(" ", $_POST['m_projects']);
for($i=0; $i<count($prid); $i++){ $prid[$i] = "id = '".$prid[$i]."'"; }
$conditions = implode(" OR ", $prid);

//mode -- w jakiej sprawie piszę
switch($_POST['m_mode']){
    case "added":   $mmhead = (count($prid) > 1) ? "Projekty dodane"
                                                : "Projekt dodany";
                    $mmcolor = "#ffaaff";
                    $mmln1 = (count($prid) > 1) ? "zlecenia $mmydop utworów zostały przyjęte, a projekty dodane do listy zleceń."
                                                : "zlecenie $mmydop utworu zostało przyjęte, a projekt dodany do listy zleceń.";
                    $mmln2 = "<strong>Proszę obserwować skrzynkę mailową!</strong> Wkrótce poinformuję o dodaniu plików.";
                    break;
    case "started": $mmhead = "Prace rozpoczęte";
                    $mmcolor = "#00ffff";
                    $mmln1 = (count($prid) > 1) ? "rozpoczynam właśnie prace nad zleconymi przez $mmybrn projektami."
                                                : "rozpoczynam właśnie prace nad zleconym przez $mmybrn projektem.";
                    $mmln2 = (count($prid) > 1) ? "Pierwsze wersje utworów mogą pojawić się już dziś lub w ciągu kilku dni. <strong>Proszę obserwować skrzynkę mailową!</strong>"
                                                : "Pierwsza wersja utworu może pojawić się już dziś lub w ciągu kilku dni. <strong>Proszę obserwować skrzynkę mailową!</strong>";
                    break;
    case "review":  $mmhead = (count($prid) > 1) ? "Utwory czekają na ocenę"
                                                : "Utwór czeka na ocenę";
                    $mmcolor = "#ff6666";
                    $mmln1 = (count($prid) > 1) ? "$mmydop utwory są gotowe. Wszystkie pliki znajdują się w katalogach, do których prowadzą poniższe linki."
                                                : "$mmydop utwór jest gotowy. Wszystkie pliki znajdują się w katalogu, do którego prowadzi poniższy link.";
                    $mmln2 = "Uprzejmie proszę o wyrażenie swojej opinii lub ewentualnych uwag celem wprowadzenia poprawek. Jeśli żadnych poprawek nie trzeba wprowadzać, proszę o kliknięcie odpowiedniego przycisku.";
                    break;
    case "redone":  $mmhead = (count($prid) > 1) ? "Wprowadzono poprawki do utworów"
                                                : "Wprowadzono poprawki do utworu";
                    $mmcolor = "#666666";
                    $mmln1 = (count($prid) > 1) ? "do $mmydop projektów zostały dodane nowe pliki z wprowadzonymi poprawkami. Wszystkie znajdują się w katalogach, do których prowadzą poniższe linki."
                                                : "do $mmydop projektu zostały dodane nowe pliki z wprowadzonymi poprawkami. Wszystkie znajdują się w katalogu, do którego prowadzi poniższy link.";
                    $mmln2 = "Uprzejmie proszę o wyrażenie swojej opinii lub ewentualnych uwag celem wprowadzenia poprawek. Jeśli żadnych poprawek nie trzeba wprowadzać, proszę o kliknięcie odpowiedniego przycisku.";
                    break;
    case "remind":  $mmhead = "Czekam na opinię";
                    $mmcolor = "#666666";
                    $mmln1 = (count($prid) > 1) ? "nie otrzymałem jeszcze definitywnej opinii co do kilku projektów. Pliki spoczywają w katalogach i czekają na ocenę."
                                                : "nie otrzymałem jeszcze definitywnej opinii co do jednego z projektów. Pliki spoczywają w katalogu i czekają na ocenę.";
                    $mmln2 = "Uprzejmie proszę o wyrażenie swojej opinii lub ewentualnych uwag celem wprowadzenia poprawek. Są one dla mnie bardzo ważne, a wręcz kluczowe do płynnego zarządzania projektami. Jeśli żadnych poprawek nie trzeba wprowadzać, proszę o kliknięcie odpowiedniego przycisku.";
                    break;
    case "paid":    $mmhead = "Potwierdzenie wpłaty";
                    $mmcolor = "#66ff66";
                    $mmln1 = (count($prid) > 1) ? "dziś na moje konto wpłynęła wpłata za projekty."
                                                : "dziś na moje konto wpłynęła wpłata za projekt.";
                    $mmln2 = "Uprzejmie dziękuję za zaufanie i skorzystanie z moich usług."; #<a href='https://www.facebook.com/wpwwCytryna'>Gorąco polecam także polajkowanie mojego fanpage'a na Facebooku.</a>
                    break;
    case "notpaid": $mmhead = (count($prid) > 1) ? "Projekty pozostają nieopłacone"
                                                : "Projekt pozostaje nieopłacony";
                    $mmcolor = "#ffdd66";
                    $mmln1 = (count($prid) > 1) ? "do dnia dzisiejszego nie otrzymałem wpłaty za przygotowane dla $mmydop projekty."
                                                : "do dnia dzisiejszego nie otrzymałem wpłaty za przygotowany dla $mmydop projekt.";
                    $mmln2 = "Proszę o dokonanie wpłaty lub potwierdzenie jej wysłania. Numer konta do przelewu to: <strong>53 1090 1607 0000 0001 1633 2919</strong> (Santander).";
                    break;
}

$to = $a['kontakt'];
if($_POST['m_tandem'] == 3 && $a['t_kontakt'] !== null) $to .= ", ".$a['t_kontakt'];
if($_POST['m_tandem'] == 2 && $a['t_kontakt'] !== null) $to = $a['t_kontakt'];
if($a['kontakt'] === null){ echo "<h1>gościu nie ma maila!</h1>"; }
else{
$subject = "[WPWW] ".$mmhead;
$from = "=?UTF-8?Q?Wojciech Przybyła | WPWW?="." <contact@wpww.pl>";


$mpass = $a['hasło'];
$mextra = $_POST['m_extra'];

$message = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
$message .= "
<link href=\"https://fonts.googleapis.com/css?family=Krona+One\" rel=\"stylesheet\">
<link href=\"https://fonts.googleapis.com/css?family=Raleway\" rel=\"stylesheet\">
<style>
h1, h2, h3, th{ font-family: 'Krona One'; }
.colordot{ color: $mmcolor; }
h3, a, .hint{ color: #926fa8; }
p, table, ul{ font-family: 'Raleway'; }
td, th{ margin: 5px 10px; border: 1px solid #b8b8b8; font-weight: normal; }
th::nth-child(1){ font-family: 'Raleway'; font-weight: bold; }
.spacious{ margin: 2em auto; }
h2.sig{ margin-bottom: 0; }
h3.sig{ margin-top: 0; }
i{ color: #b8b8b8; }
.hint{ font-size: 2em; }
</style>
</head>
<body>
<center>
<h1><span class=colordot>«</span> $mmhead <span class=colordot>»</span></h1>
<hr>
<p>$mmhnrf,</p>
<p>$mmln1</p>
<table>
<tr><td>Tytuł utworu</td><td>ID projektu</td><td>Link do katalogu</td></tr>
";

//kwerenda
$q = "SELECT id, nazwa, tytuł, album, link FROM p_projekty WHERE $conditions";
$r = $conn->query($q) or die($q."<br>".$conn->error);
while($b = $r->fetch_assoc()){
    if($b['album'] === null) $msong = ($b['tytuł'] === null) ? "—" : $b['tytuł'];
        else $msong = $b['album']." – ".$b['tytuł'];
    $mprid = $b['id'];
    $mproj = ($b['nazwa'] === null) ? $b['id'] : $b['id']." ".$b['nazwa'];
    $mlink = $b['link']; if($b['link'] === null) die("Projekt nie ma folderu");
    
    $message .= "<tr><th>$msong</th><th><a href='http://projects.wpww.pl/?p=$mprid'>$mproj</a></th><th><a href='$mlink'>Przejdź do Sejfu</a></th></tr>";
}
$r->free_result();

$message .= "
</table>
<p>".nl2br($mextra)."</p>
<p>$mmln2</p>
<p class=spacious><i>Wszystkie zlecone przez $mmybrn projekty wraz z informacjami na temat opłat są dostępne w <a href=\"http://projects.wpww.pl/\">katalogu moich projektów</a> po zalogowaniu hasłem <strong>$mpass</strong></i></p>
<p>Pozdrawiam serdecznie,</p>
<h2 class=sig>Wojciech Przybyła</h2><h3 class=sig>WPWW – Muzyka szyta na miarę</h3>
<img src='http://hire.wpww.pl/media/logo.png' alt='logo' width=100>
<hr>
<p><i>Wiadomość wysłana automatycznie. Odpowiedzi na nią zostaną przekierowane na adres <a href='mailto:contact@wpww.pl'>contact@wpww.pl</a></i></p>
</center>
</body>
</html>
";

// Always set content-type when sending HTML email
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

// More headers
$headers .= 'From: '.$from. "\r\n";
$headers .= 'Reply-To: contact@wpww.pl'. "\r\n";
//$headers .= 'Cc: contact@wpww.pl'. "\r\n";
//confirm
$headers .= 'X-Confirm-Reading-To: contact@wpww.pl'. "\r\n";
$headers .= 'Disposition-Notification-To: contact@wpww.pl'. "\r\n";
$headers .= 'Return-Receipt-To: contact@wpww.pl'. "\r\n";

mail($to,$subject,$message,$headers);

//dla celów testowych wysyłaj sam sobie
//mail('thehydromancer@gmail.com', $subject, $message, $headers);

?>
<section>
<h2 style='color: #66ff66; text-shadow: 0 0 5px black;'>Wiadomość wysłana!</h2>
</section>

<?php
}
}
?>

<?php
if($a['contactpreference'] != null) print "<span style='font-weight: bold'>Uwaga: klient ma preferencję kontaktową: ".$a['contactpreference']."</span>";
?>

<form method=post>
    <div class=flexright>
        <h3>Odmień imię<h3>
        <input type="text" name="m_who" value="<?php echo $odbiorca['imiewolacz']; ?>"></input>
        <h3>Piszemy do <b><?php echo ($odbiorca['kobieta']) ? "kobiety" : "mężczyzny"; ?></b>?</h3>
        <input type="checkbox" name="m_sexcorr" checked></input>
        <input type="hidden" name="m_sex" value="<?php echo $odbiorca['kobieta']; ?>"></input>
<?php
if($tandem !== false){
?>
        <h3>Tandem</h3>
        <select name='m_tandem'>
            <option value='3' selected>do obu</option>
            <option value='1'><?php echo $tandem[0]; ?></option>
            <option value='2'><?php echo $tandem[1]; ?></option>
        </select>
<?php
}
?>
    </div>
    <div class=flexright>
        <h3>ID projektów (dodaj więcej po spacjach)</h3>
        <input type="text" name="m_projects" value="<?php echo $_GET['id']; ?>"></input>
    </div>
    <div class=flexright>
        <h3>O czym piszemy?</h3>
        <input type="radio" name="m_mode" value="added"<?php if($_GET['mode'] == 1) echo " checked"; ?> required><span style="background: #ffaaff44;"
            title="zlecenie utworu zostało przyjęte, a projekt dodany do listy zleceń. Proszę obserwować skrzynkę mailową! Wkrótce poinformuję o dodaniu plików."
            >Otwarty</span></input>
        <input type="radio" name="m_mode" value="started"<?php if($_GET['mode'] == 2) echo " checked"; ?> required><span style="background: #00ffff44;"
            title="rozpoczynam właśnie prace nad zleconym projektem. Pierwsza wersja utworu może pojawić się już dziś lub w ciągu kilku dni. Proszę obserwować skrzynkę mailową!"
            >Odpalam prace</span></input>
        <input type="radio" name="m_mode" value="review"<?php if($_GET['mode'] == 3) echo " checked"; ?> required><span style="background: #ff666644;"
            title="utwór jest gotowy. Wszystkie pliki znajdują się w katalogu, do którego prowadzi poniższy link. Uprzejmie proszę o wyrażenie swojej opinii lub ewentualnych uwag celem wprowadzenia poprawek."
            >Ocena</span></input>
        <input type="radio" name="m_mode" value="redone"<?php if($_GET['mode'] == 3.5) echo " checked"; ?> required><span style="background: #66666644;"
            title="do projektu zostały dodane nowe pliki z wprowadzonymi poprawkami. Wszystkie znajdują się w katalogu, do którego prowadzi poniższy link. Uprzejmie proszę o wyrażenie swojej opinii lub ewentualnych uwag celem wprowadzenia poprawek."
            >Ocena korekty</span></input>
        <input type="radio" name="m_mode" value="remind"<?php if($_GET['mode'] == 3.9) echo " checked"; ?> required><span style="background: #66666644; color: red;"
            title="nie otrzymałem jeszcze definitywnej opinii co do jednego z projektów. Pliki spoczywają w katalogu i czekają na ocenę. Uprzejmie proszę o wyrażenie swojej opinii lub ewentualnych uwag celem wprowadzenia poprawek. Są one dla mnie bardzo ważne, a wręcz kluczowe do płynnego zarządzania projektami."
            >Odpowiedz!</span></input>
        <input type="radio" name="m_mode" value="paid"<?php if($_GET['mode'] == 4) echo " checked"; ?> required><span style="background: #66ff6644;"
            title="dziś na moje konto wpłynęła wpłata za projekt. Pliki znajdujące się w katalogu są od teraz dostępne do pobrania. Uprzejmie dziękuję za zaufanie i skorzystanie z moich usług."
            >Opłacony</span></input>
        <input type="radio" name="m_mode" value="notpaid"<?php if($_GET['mode'] == 4.9) echo " checked"; ?> required><span style="background: #ffdd6644; color: red;"
            title="do dnia dzisiejszego nie otrzymałem wpłaty za przygotowany projekt. Proszę o dokonanie wpłaty lub potwierdzenie jej wysłania. Numer konta do przelewu to: 123"
            >Oddaj piniundze!</span></input>
    </div>
    <h3>Tekst dodatkowy</h3>
    <div class='flexright'>
    <textarea id='textmaker' name="m_extra" style="width: 30vw; height: 20vh; font-family: Raleway; font-size: 18px; background: none;"></textarea>
    <div style="width: 30vw;"><p id='preview'></p></div>
    </div>
    <script>
        function loadPreview(){
            document.getElementById("preview").innerHTML = document.getElementById('textmaker').value.replace(/\n/, "<br>");
        }
        setInterval(loadPreview, 2000);
    </script>
    <br><input type="submit" name="m_sub" value="Ziu!"></input>
</form>

<p>Wróć do <b><a href="http://projects.wpww.pl/archmage.php?c=clients">szpicy</a></b> albo <b><a href="http://projects.wpww.pl">listy projektów</a></b></p>

<?php
#####
generateBottom("main");
?>