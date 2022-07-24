<?php
session_start();
require("cue.php");
giveMeTheCue(0);

if($_GET['logout']){
    session_unset();
}

if(!isset($_SESSION['whoami'])){
    $_SESSION['wherewasi'] = $_SERVER['REQUEST_URI'];
    $iamblessed = (isset($_GET['b'])) ? "?b=".$_GET['b'] : "";
    header("Location: http://projects.wpww.pl/auth.php".$iamblessed);
    die();
}

//wyszukiwarka
if(isset($_GET['s_sub'])){
    /*
    $q = "SELECT id FROM p_projekty WHERE tytuł LIKE '%".$_GET['s_title']."%'";
    $r = $conn->query($q) or die($conn->error);
    $a = $r->fetch_assoc();
    $r->free_result;
    if($a['id'] != ""){
        header("Location: http://projects.wpww.pl/?p=".$a['id']);
        die();
    }else{
        print "Nie znaleziono ani jednego tytułu.";
    }
    */
    $andfindtitles = " AND (tytuł LIKE '%".$_GET['s_title']."%' OR album LIKE '%".$_GET['s_title']."%')";
}

//filtr klientów
if(isset($_GET['cl'])){
    $andonlybythisguy = " AND inspiracja = ".$_GET['cl']."";
}

$lang = ($_GET['l'] == "en") ? true : false;
generateHead(($lang) ? "Project list" : "Lista projektów", 0, $lang);
require("variableconverter.php");

function project($id, $isthin, $nazwa, $status, $link = null, $cena = null){
    //tłumaczenie statusu
    switch($status){
        case 0: $status = "done"; break;
        case 1: $status = "undone"; break;
        case 2: $status = "scrapped"; break;
        case 3: $status = "fused"; break;
        case 4: $status = "free";
    }
    //alanizator linka
    if($link != null){
        if(preg_match('/brzoskwinia/', $link)) $linkclass='link_brzoskwinia';
        if(preg_match('/youtube/', $link)) $linkclass='link_youtube';
        if(preg_match('/facebook/', $link)) $linkclass='link_facebook';
        if(preg_match('/projects/', $link) && $_SESSION['whoami'] != "incognito") $linkclass='link_sejf';
    }
    print "<div class='project ";
        if($isthin) print "thin ";
    print "class".substr($id, 0, 1)." ";
    print "$status drop-shadow interactive'>";
    if($link != null) print "<div class='linkindicator $linkclass'></div>";
    if($cena != null) print "<div class='priceindicator'>".$cena."</div>";
    print "<p>$id</p>";
    print "<h2>$nazwa</h2>";
    print "<div class='hidemaster'>";
    // print "•";
    print "</div></div>";
}

?>

<div class="home">
<h1><?php echo ($lang) ? "Project list" : "Lista projektów"; ?></h1>

<div class="flexright">
<?php
if($_SESSION['whoami'] == "archmage"){ //arcymag może zrobić filtry czerwonych
    if($_GET['justreds']){
        $andmaybejustreds = " AND status = 1";
        print "<a href='/'><button>Wszystkie</button></a>";
    }else{
        print "<a href='?justreds=1'><button>Tylko czerwone</button></a>";
    }
}
//każdy może zrobić układ liniowy
if($_GET['linearview']){
    print "<a href='/'><button>Kategoriami</button></a>";
}else{
    print "<a href='?linearview=1'><button>Liniowo</button></a>";
}
?>
<form method=get>
    <input type='text' name='s_title' placeholder="Szukaj tytułu" />
    <input type="submit" name="s_sub" value="»" />
</form>
</div>


<?php
########################## ARCYMAG I PLEBS ###############################
if($_SESSION['whoami'] == "archmage" || $_SESSION['whoami'] == "incognito"){
    if($_GET['linearview']){
?>
<section>
<div>
<?php
    //arcymag -- dodaj nowy projekt i zarządzaj klientelą
    if($_SESSION['whoami'] == "archmage"){ print "<a href='archmage.php?e=new' style=\"display: inline-block\">"; project("+", false, "Nowy...", 4); print "</a>"; }
    if($_SESSION['whoami'] == "archmage"){ print "<a href='archmage.php?c=clients' style=\"display: inline-block\">"; project("+", false, "Klienci", 4); print "</a>"; }
    
    $q = "SELECT id, nazwa, status, tytuł, link FROM p_projekty WHERE 1=1$andmaybejustreds $andfindtitles $andonlybythisguy ORDER BY data_in DESC";
    $r = $conn->query($q) or die($conn->error);
    $i = 0;
    while($a = $r->fetch_assoc()){
        $isthin = ($a['nazwa'] === null) ? true : false;
        $nazwa = ($isthin) ? $a['tytuł'] : $a['nazwa'];
        project($a['id'], $isthin, $nazwa, $a['status'], $a['link']);
        $i++;
    }
    $r->free_result();
?>
</div>
<p><?php echo ($lang) ? "Projects in total: $i" : "Projektów łącznie: $i"; ?></p>
</section>
<?php }else{ 
if(!isset($_GET['cl'])){
?>
<section>
<h2><?php echo ($lang) ? "Originals" : "Utwory własne"; ?></h2>
<div class="list">
<?php
    //arcymag -- dodaj nowy projekt
    if($_SESSION['whoami'] == "archmage"){ print "<a href='archmage.php?e=new' style=\"display: inline-block\">"; project("+", false, "Nowy...", 4); print "</a>"; }
    
    $q = "SELECT id, nazwa, status, tytuł, link FROM p_projekty WHERE (id LIKE 'F%%' OR id LIKE 'A%%')$andmaybejustreds $andfindtitles ORDER BY id DESC";
    $r = $conn->query($q) or die($conn->error);
    $i = 0;
    while($a = $r->fetch_assoc()){
        $isthin = ($a['nazwa'] === null) ? true : false;
        $nazwa = ($isthin) ? $a['tytuł'] : $a['nazwa'];
        project($a['id'], $isthin, $nazwa, $a['status'], $a['link']);
        $i++;
    }
    $r->free_result();
?>
</div>
<p><?php echo ($lang) ? "Originals in total: $i" : "Utworów własnych łącznie: $i"; ?></p>
</section>

<section>
<h2><?php echo ($lang) ? "Covers and cooperations" : "Covery i współprace"; ?></h2>
<div class="list">
<?php
    $q = "SELECT id, nazwa, status, tytuł, link FROM p_projekty WHERE (id LIKE 'V%%' OR id LIKE 'C%%')$andmaybejustreds $andfindtitles ORDER BY data_in DESC, id DESC";
    $r = $conn->query($q) or die($conn->error);
    $i = 0;
    while($a = $r->fetch_assoc()){
        $isthin = ($a['nazwa'] === null) ? true : false;
        $nazwa = ($isthin) ? $a['tytuł'] : $a['nazwa'];
        project($a['id'], $isthin, $nazwa, $a['status'], $a['link']);
        $i++;
    }
    $r->free_result();
?>
</div>
<p><?php echo ($lang) ? "Co's in total: $i" : "Alternatyw łącznie: $i"; ?></p>
</section>

<?php } //klamra GET 'cl' ?>
<section>
<h2><?php echo ($lang) ? "Requests" : "Utwory na zlecenie"; ?></h2>
<div class="list">
<?php
    //arcymag -- zarządzaj klientelą
    if($_SESSION['whoami'] == "archmage"){ print "<a href='archmage.php?c=clients' style=\"display: inline-block\">"; project("+", false, "Klienci", 4); print "</a>"; }
    
    $q = "SELECT id, nazwa, status, tytuł, link FROM p_projekty WHERE (id LIKE 'Z%%' OR id LIKE 'K%%')$andmaybejustreds $andfindtitles $andonlybythisguy ORDER BY data_in DESC, id DESC";
    $r = $conn->query($q) or die($conn->error);
    $i = 0;
    while($a = $r->fetch_assoc()){
        $isthin = ($a['nazwa'] === null) ? true : false;
        $nazwa = ($isthin) ? $a['tytuł'] : $a['nazwa'];
        project($a['id'], $isthin, $nazwa, $a['status'], $a['link']);
        $i++;
    }
    $r->free_result();
?>
</div>
<p><?php echo ($lang) ? "Requests in total: $i" : "Zleceń łącznie: $i"; ?></p>
</section>
<?php }}else{ ########################## SPECJALIŚCI ###############################
?>
<section>
<h3>Zalogowany jako <?php echo $_SESSION['whoami']; ?></h3>
<h2><?php echo ($lang) ? "Your projects" : "Twoje projekty"; ?></h2>
<div class="list">
<?php
    $q = "SELECT p.id, p.nazwa, p.status, p.tytuł, p.link, q.cena, q.data_5
    FROM p_projekty p
        LEFT JOIN p_questy q ON p.id = q.id
    WHERE inspiracja = ".$_SESSION['clientid']."
    ORDER BY id DESC";
    $r = $conn->query($q) or die($conn->error);
    $i = 0;
    while($a = $r->fetch_assoc()){
        $isthin = ($a['nazwa'] === null) ? true : false;
        $nazwa = ($isthin) ? $a['tytuł'] : $a['nazwa'];
            if($a['data_5'] != null || $a['status'] == 2) $a['cena'] = null; //jeśli opłacony, to nie wyświetlaj ceny
        project($a['id'], $isthin, $nazwa, $a['status'], $a['link'], $a['cena']);
        $i++;
    }
    $r->free_result();
?>
</div>
<p><?php echo ($lang) ? "Requests in total: $i" : "Zleceń łącznie: $i"; ?></p>
</section>
<section>
<?php
//zliczanie niedopłat
$finance = ['accepted' => 0, 'total' => 0];

$q = "SELECT q.cena, q.data_4, q.data_5 FROM p_questy q
        LEFT JOIN p_projekty p ON p.id = q.id
        WHERE p.status <> 2 AND q.klient_id = ".$_SESSION['clientid'];
$r = $conn->query($q) or die($conn->error);
while($a = $r->fetch_array()){
    if($a['data_5'] == null){
        $finance['total'] += $a['cena'];
        if($a['data_4'] != null) $finance['accepted'] += $a['cena'];
    }
}
$r->free_result();
//budżet
$q = "SELECT budget FROM p_klienci WHERE id = ".$_SESSION['clientid'];
$r = $conn->query($q) or die($conn->error);
$a = $r->fetch_assoc();
$r->free_result();
$a['budget'];
?>
<h2><?php echo ($lang) ? "Finances": "Finanse"; ?></h2>
<h3><?php echo ($lang) ? "Has to be paid for:": "Do opłacenia za:"; ?></h3>
<table>
<tr>
    <td><?php echo ($lang) ? "confirmed projects": "zatwierdzone projekty"; ?></td>
    <th style='color: #f6d366;'><?php echo $finance['accepted']." zł"; ?></th>
</tr>
<tr>
    <td><?php echo ($lang) ? "in total": "łącznie"; ?></td>
    <th><?php echo $finance['total']." zł"; ?></th>
</tr>
<tr>
    <td><?php echo ($lang) ? "account balance": "nadpłaty"; ?></td>
    <th><?php echo $a['budget']." zł"; ?></th>
</tr>
</table>
</section>
<?php } ?>
</div>

<?php ########################## CZARNA PŁACHTA, CZYLI INFO ############################### ?>
<div class="details">
<span id="detailsclose" class="closeicon interactive">×</span>
<h1><?php echo ($lang)? "Project details" : "Szczegóły projektu" ;?></h1>
<div class="flexdown">
    <table>
        <tr>
            <td><?php echo ($lang) ? "Project ID" : "ID projektu"; ?></td>
            <td colspan=3><?php echo ($lang) ? "Name" : "Nazwa"; ?></td>
            <td><?php echo ($lang) ? "Status (PL)" : "Status"; ?></td>
        </tr>
        <tr>
            <td id="d_id">-</td>
            <td colspan=3 id="d_name">-</td>
            <td id="d_status">-</td>
        </tr>
        <tr>
            <td id="d_titlelabel">-</td>
            <td id="d_albumlabel">-</td>
            <td id="d_inspilabel"><?php echo ($lang) ? "Inspired by (PL)" : "Inspiracja"; ?></td>
            <td><?php echo ($lang) ? "Date in" : "Data powołania"; ?></td>
            <td><?php echo ($lang) ? "Date out" : "Data ukończenia"; ?></td>
        </tr>
        <tr>
            <td id="d_title">-</td>
            <td id="d_album">-</td>
            <td id="d_inspired">-</td>
            <td id="d_datein">-</td>
            <td id="d_dateout">-</td>
        </tr>
    </table>
    <div id="d_link">
        
    </div>
    <?php if($_SESSION['whoami'] == "archmage"){ ?>
    <a href="archmage.php?e=dunno">Edytuj</a>
    <?php } ?>
</div>
</div>

<button onclick="window.location.href = '?logout=1'">Wyloguj się</button>

<?
generateBottom("main");
?>