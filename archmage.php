<?php
session_start();
require("cue.php");
giveMeTheCue(0);

if(!isset($_SESSION['whoami']) || $_SESSION['whoami'] != "archmage"){
    $_SESSION['wherewasi'] = $_SERVER['REQUEST_URI'];
    header("Location: http://projects.wpww.pl/auth.php");
    die();
}

$lang = ($_GET['l'] == "en") ? true : false;
generateHead("Szpica arcymaga", 0, $lang);

?>

<div class="home">
<h1>Szpica arcymaga</h1>

<?php
//wczytaj ustawienia
$q = "SELECT * FROM p_opcje";
$r = $conn->query($q) or die($conn->error);
while($a = $r->fetch_assoc()){
    $_OPTIONS[$a['par']] = $a['val'];
}
$r->free_result;

// submit nowego projektu
if(isset($_POST['n_submit'])){
    $projectname = ($_POST['n_name'] == "") ? "null" : "'".$_POST['n_name']."'";
    $client = ($_POST['n_inspiredclient']) ? $_POST['n_client'] : "'".$_POST['n_inspired']."'";
    $link = ($_POST['n_inspiredclient']) ? "'http://projects.wpww.pl/safe/?p=".$_POST['n_id']."'" : "null";
    
    $q = "INSERT INTO p_projekty VALUES
        ('".$_POST['n_id']."', $projectname, 1, 'błądzi', null, null, $client, '".date("Y-m-d")."', null, $link)";
    $conn->query($q) or die($q."<br>makeproject<br>".$conn->error);
    $heraldictext = "<span class='green-tick'>✓</span>Dodano nowy projekt: ".$_POST['n_id']." ".$_POST['n_name'];
    
    if($_POST['n_inspiredclient']){ //jeśli to zlecenie
        //dodaj questa
        $q = "INSERT INTO p_questy VALUES
            ('".$_POST['n_id']."', '".$_POST['n_client']."', '".date('Y-m-d')."', null, null, null, null, null, null, null)";
        $conn->query($q) or die($q."<br>makequest<br>".$conn->error);
        $heraldictext .= ($paid != "null") ? ". Wklepano od razu jako opłacony quest." : ". Wklepano od razu jako quest.";
        
        //dodaj folder do sejfu
        mkdir($_SERVER['DOCUMENT_ROOT']."/safe/".$_POST['n_id']);
        chmod($_SERVER['DOCUMENT_ROOT']."/safe/".$_POST['n_id'], 0751);
        $heraldictext .= " Dodano także katalog.";
    }
    header("Location: http://projects.wpww.pl/archmage.php?e=".$_POST['n_id']);
    die();
}
// submit edytowanego projektu
if(isset($_POST['e_submit'])){
    $etytul = ($_POST['e_title'] == "") ? "null" : "'".str_replace("'", "''", $_POST['e_title'])."'";
    $ealbum = ($_POST['e_album'] == "") ? "null" : "'".str_replace("'", "''", $_POST['e_album'])."'";
    if($_POST['e_inspiredclient']){
        $einspir = $_POST['e_client'];
        $changequesttoo = true;
    }else{
        $einspir = ($_POST['e_inspired'] == "") ? "null" : "'".str_replace("'", "''", $_POST['e_inspired'])."'";
    }
    $edataout = ($_POST['e_date_out'] == "") ? "null" : "'".date_format(date_create($_POST['e_date_out']), "Y-m-d")."'";
    $edeadline = ($_POST['e_deadline'] == "") ? "null" : "'".date_format(date_create($_POST['e_deadline']), "Y-m-d")."'";
    if($_POST['e_issafelink']){
        $q = "SELECT hasło FROM p_klienci WHERE id = $einspir";
        $r = $conn->query($q) or die($conn->error);
        $pass = $r->fetch_array();
        $r->free_result();
        $elink = "'http://projects.wpww.pl/safe/?p=".$_POST['e_id']."&b=".$pass[0]."'";
    }else{
        $elink = ($_POST['e_link'] == "") ? "null" : "'".str_replace("'", "''", $_POST['e_link'])."'";
    }
    $_POST['e_details'] = ($_POST['e_details'] == "") ? "null" : "'".$_POST['e_details']."'";
    
    $q = "UPDATE p_projekty SET
            status = ".$_POST['e_status'].",
            status_opis = '".$_POST['e_status_opis']."',
            tytuł = $etytul,
            album = $ealbum,
            inspiracja = $einspir,
            data_out = $edataout,
            link = $elink
        WHERE id = '".$_POST['e_id']."'";
    $conn->query($q) or die($conn->error);
    $heraldictext = "<span class='green-tick'>✓</span>Wprowadzono zmiany do projektu ".$_POST['e_id'].".";
    
    if($changequesttoo){
        //budżet
        $q = "SELECT budget FROM p_klienci WHERE id = ".$_POST['e_client'];
        $r = $conn->query($q) or die($q."<br>budgetcheck<br>".$conn->error);
        $a = $r->fetch_assoc();
        $r->free_result();
        if($a['budget'] >= $_POST['e_price'] && $_POST['e_nopriceyet']){ 
            //odbij
            $odbijjuzoplacone = "data_5 = '".date('Y-m-d')."',";
            //odejmij
            $q = "UPDATE p_klienci SET budget = ".($a['budget'] - $_POST['e_price'])." WHERE id = $einspir";
            $conn->query($q) or die($q.$conn->error);
            $heraldictext .= " Opłacono z budżetu.";
        }
        
        $q = "UPDATE p_questy SET
                klient_id = ".$_POST['e_client'].",
                deadline = $edeadline,
                $odbijjuzoplacone
                cena = ".$_POST['e_price'].",
                zyczenia = ".$_POST['e_details']."
            WHERE id = '".$_POST['e_id']."'";
        $conn->query($q) or die($conn->error);
        $heraldictext .= " Poprawiono też questa.";
        
        //jeśli scrappuję projekt klienta, usuń folder
        if($_POST['e_status'] == 2) print "<script>window.location = 'http://projects.wpww.pl/archmage.php?c=clear&cp=".$_POST['e_id']."';</script>";
    }
}
//submit dodawania klienta
if(isset($_POST['c_submit'])){
    //wysublimuj nazwisko i maila
    $delimiters = [strpos($_POST['c_name'], "<"), strpos($_POST['c_name'], ">"),
                    strpos($_POST['c_name'], "("), strpos($_POST['c_name'], ")"),
                    strpos($_POST['c_name'], "{"), strpos($_POST['c_name'], "}")];
    $c_name = substr($_POST['c_name'], 0, $delimiters[0]-1);
    $c_mail = substr($_POST['c_name'], $delimiters[0]+1, $delimiters[1]-$delimiters[0]-1);
        $c_mail = ($c_mail == "" || $c_mail == "brak") ? "NULL" : "'$c_mail'";
    $c_tele = substr($_POST['c_name'], $delimiters[2]+1, $delimiters[3]-$delimiters[2]-1);
        $c_tele = ($c_tele == "" || $c_tele == "brak") ? "NULL" : $c_tele;
    $c_pref = substr($_POST['c_name'], $delimiters[4]+1, $delimiters[5]-$delimiters[4]-1);
        $c_pref = ($c_pref == "" || $c_pref == "brak" || $delimiters[4] === FALSE) ? "NULL" : "'$c_pref'";
    
    //lista obecnych klientów (i ich haseł)
    $q = "SELECT * FROM p_klienci";
    $r = $conn->query($q) or die($conn->error);
    while($a = $r->fetch_assoc()){
        $clients['hasła'][] = $a['hasło'];
        $clients['maile'][] = $a['kontakt'];
        $clients['maile'][] = $a['t_kontakt'];
    }
    $r->free_result;
    
    //sprawdź unikatowość klienta wg maili
    foreach($clients['maile'] as $mtc){ //mail to check
        if($mtc == $c_mail && !strpos($c_mail, "@")) die("Taki mail już jest.");
    }
    
    //generuj hasło
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    do{
        //sprawdź unikatowość
        $flag = false;
        $haslo = substr(str_shuffle($chars),0,12);
        foreach($clients['hasła'] as $ptc){ //password to check
            if($ptc == $haslo) $flag = true;
        }
    }while($flag);
    
    $q = "INSERT INTO p_klienci VALUES
        ('', '$c_name', '$haslo', $c_mail, $c_tele, $c_pref, null, null, 0, 0, 0)";
    $conn->query($q) or die($conn->error);
    $heraldictext = "<span class='green-tick'>✓</span>Dodano nowego klienta: $c_name • $c_mail • $c_tele • $c_pref";
}
//submit oznaczania krętacza i oszusta
if(isset($_GET['kio'])){
    if(is_numeric($_GET['kio'])){ //onegdaj można było zablokować indywidualne projekty
        $q = "UPDATE p_klienci SET kio = 1 WHERE id = ".$_GET['kio'];
    }else{
        $q = "UPDATE p_questy SET kio = 1 WHERE id = '".$_GET['kio']."'";
    }
    $conn->query($q) or die($q."<br>".$conn->error);
    $heraldictext = "<span class='green-tick'>✓</span>Status zaktualizowany.";
    header("Location: http://projects.wpww.pl/archmage.php?c=clients");
    die();
}
//submit oznaczania "już ci ufam"
if(isset($_GET['jcu'])){
    if(is_numeric($_GET['jcu'])){ //onegdaj można było zablokować indywidualne projekty
        $q = "UPDATE p_klienci SET kio = 0 WHERE id = ".$_GET['jcu'];
    }else{
        $q = "UPDATE p_questy SET kio = 0 WHERE id = '".$_GET['jcu']."'";
    }
    $conn->query($q) or die($conn->error);
    $heraldictext = "<span class='green-tick'>✓</span>Status zaktualizowany.";
    header("Location: http://projects.wpww.pl/archmage.php?c=clients");
    die();
}
//tickery odbijania dat
if($_GET['c'] == "tick"){
    $whattobop = $_GET['cd'];
    $wheretobop = $_GET['cp'];
    $q = "UPDATE p_questy SET data_$whattobop = '".date('Y-m-d')."' WHERE id = '$wheretobop'";
    $conn->query($q) or die($conn->error);
    
    // automatyczne modyfikowanie statusu projektów
    switch($whattobop){
        case 2: $whattosay = "czeka na recenzję"; break;
        case 3: $whattosay = "obróbka trwa"; break;
        case 4: $whattosay = "ukończony"; break;
    }
    if($whattobop != 5){
        $q = ($whattobop == 4) ? "UPDATE p_projekty SET status = 0, status_opis = '$whattosay', data_out = '".date('Y-m-d')."' WHERE id = '$wheretobop'"
                                : "UPDATE p_projekty SET status_opis = '$whattosay' WHERE id = '$wheretobop'";
    }
    $conn->query($q) or die($conn->error);
    
    $heraldictext = "<span class='green-tick'>✓</span>Status questa zaktualizowany.";
    header("Location: http://projects.wpww.pl/archmage.php?c=clients");
    die();
}
//tickery czyszczenia wygasłych folderów
if($_GET['c'] == "clear"){
    $wheretobop = str_replace(",", "','", $_GET['cp']);
    $q = "UPDATE p_projekty SET link = null WHERE id IN('$wheretobop')";
    $conn->query($q) or die($conn->error);
    
    //usuń folder
    foreach(explode(",", $_GET['cp']) as $x => $id){
        $target = $_SERVER['DOCUMENT_ROOT']."/safe/".$id;
        $files = scandir($target);
        for($i=2; $i<count($files); $i++){
            unlink($target."/".$files[$i]);
        }
        rmdir($target);
    }
    
    echo "Wyczyszczone zostały repozytoria projektów: ".str_replace(",", ", ", $_GET['cp']).". <a href='?c=clients'>Wróć do szpicy</a>.";
    
    //header("Location: http://projects.wpww.pl/archmage.php?c=clients");
    die();
}

#################################### tryb edycji ###############################
if(isset($_GET['e'])){
    if($_GET['e'] == "new"){
        ########################## dodaj nowy ############################
        // wyszukaj najnowsze ID projektów
        for($i="F"; $i != "END"; $i){
            $q = "SELECT id FROM `p_projekty` WHERE id LIKE '$i%' ORDER BY id DESC LIMIT 1";
            $r = $conn->query($q) or die($conn->error);
            $a = $r->fetch_assoc();
            $newest[$i] = $a['id'];
            $r->free_result();
            
            //inkrementacja
            //obedrzyj id z inicjału
            $initial = $newest[$i][0]; $one = $newest[$i][1]; $two = $newest[$i][2];
            ++$two;
            if($two == "10") $two = "A";
            if($two == "AA"){
                $two = "0";
                ++$one;
                if($one == "10") $one = "A";
                if($one == "AA") die("SKOŃCZYŁA SIĘ NUMERACJA");
            }
            $newest[$i] = $initial.$one.$two;
            
            //zmiana iteratora
            switch($i){
                case "F": $i = "V"; break;
                case "V": $i = "C"; break;
                case "C": $i = "Z"; break;
                case "Z": $i = "K"; break;
                case "K": $i = "END";
            }
        }
        
echo $heraldictext;
?>
<h2>Dodaj nowy projekt</h2>
<form method=post>
    <h3>Rodzaj</h3>
    <input type="radio" name="n_id" value="<?php echo $newest["F"]; ?>" checked>Własny (<?php echo $newest["F"]; ?>)</input>
    <input type="radio" name="n_id" value="<?php echo $newest["V"]; ?>">Cover (<?php echo $newest["V"]; ?>)</input>
    <input type="radio" name="n_id" value="<?php echo $newest["C"]; ?>">Kooperacja (<?php echo $newest["C"]; ?>)</input>
    <input type="radio" name="n_id" value="<?php echo $newest["Z"]; ?>" <?php if(isset($_GET['whose'])) print " checked" ?>>Zlecenie (<?php echo $newest["Z"]; ?>)</input>
    <input type="radio" name="n_id" value="<?php echo $newest["K"]; ?>">Szybki quest (<?php echo $newest["K"]; ?>)</input>
    <h3>Nazwa</h3>
    <input type="text" name="n_name" onchange="am_untick('n_name0', this.value);"></input>
    <!--<input type="checkbox" name="n_name0" checked>Brak</input>-->
    <h3>Inspiracja</h3>
    <div class="flexright">
        <div>
            <input type="radio" name="n_inspiredclient" id="n_inspiredclient0" value=0 checked></input><label for="n_inspiredclient0">standardowa</label><br>
            <input type="text" name="n_inspired"></input>
        </div>
        <div>
            <input type="radio" name="n_inspiredclient" id="n_inspiredclient1" value=1<?php if(isset($_GET['whose'])) print " checked"; ?>></input><label for="n_inspiredclient1">klient</label><br>
            <select name="n_client">
                <?php
                $q = "SELECT id, nazwisko FROM p_klienci WHERE id > 1 ORDER BY id DESC";
                $r = $conn->query($q) or die($conn->error);
                while($a = $r->fetch_assoc()){
                    print "<option value=".$a['id'];
                    if($_GET['whose'] == $a['id']) print " selected";
                    print ">".$a['nazwisko']."</option>";
                }
                $r->free_result;
                ?>
            </select>
        </div>
    </div>
    <br>
    <input type="submit" name="n_submit" value="Zatwierdź"></input>
</form>
<?php
    }else{
        ########################## edytuj istniejący ############################
        //zbierz dane
        
        $q = "SELECT p.id, p.nazwa, p.status, p.status_opis, p.tytuł, p.album, p.inspiracja, p.data_out, p.link, c.hasło
            FROM p_projekty p
                LEFT JOIN p_klienci c ON p.inspiracja = c.id
            WHERE p.id = '".$_GET['e']."'";
        $r = $conn->query($q) or die($conn->error);
        $a = $r->fetch_assoc();
        $r->free_result();
        if(is_numeric($a['inspiracja'])){
            $q = "SELECT deadline, cena, zyczenia FROM p_questy WHERE id = '".$a['id']."'";
            $r = $conn->query($q) or die($conn->error);
            $b = $r->fetch_assoc();
            $r->free_result();
            foreach($b as $name => $value){ $a[$name] = $value; }
        }
echo $heraldictext;
?>
<h2>Edytuj projekt <?php echo $a['id']." ".$a['nazwa']; ?></h2>
<form method=post>
    <h3>Faza</h3>
    <input type="radio" name="e_status" value=0 <?php if($a['status'] == 0) echo "checked"; ?>>done</input>
    <input type="radio" name="e_status" value=1 <?php if($a['status'] == 1) echo "checked"; ?>>undone</input>
    <input type="radio" name="e_status" value=2 <?php if($a['status'] == 2) echo "checked"; ?>>scrapped</input>
    <input type="radio" name="e_status" value=3 <?php if($a['status'] == 3) echo "checked"; ?>>fused</input>
    <input type="text" name="e_status_opis" placeholder="Opis" value="<?php echo $a['status_opis']; ?>"></input>
    <h3>Dane utworu</h3>
    <input type="text" name="e_title" placeholder="Tytuł" <?php if($a['tytuł'] != null) echo "value=\"".$a['tytuł']."\""; ?> onchange="am_untick('e_title0', this.value);"></input>
    <!--<input type="checkbox" name="e_title0" <?php if($a['tytuł'] == null) echo "checked"; ?>>Brak</input>-->
    <input type="text" name="e_album" placeholder="<?php echo (substr($a['id'],0,1) == "Z") ? "Wykonawca" : "Album"; ?>" <?php if($a['album'] != null) echo "value=\"".$a['album']."\""; ?> onchange="am_untick('e_album0', this.value);"></input>
    <!--<input type="checkbox" name="e_album0" <?php if($a['album'] == null) echo "checked"; ?>>Brak</input>-->
    <h3>Inspiracja</h3>
    <div class="flexright">
        <div>
            <input type="radio" name="e_inspiredclient" id="e_inspiredclient0" value=0 <?php if(!is_numeric($a['inspiracja'])) print "checked"; ?>></input><label for="e_inspiredclient0">standardowa</label><br>
            <input type="text" name="e_inspired" <?php if($a['inspiracja'] != null && !is_numeric($a['inspiracja'])) echo "value='".$a['inspiracja']."'"; ?> onchange="am_untick('e_inspired0', this.value);"></input><br>
            <!--<input type="checkbox" name="e_inspired0" <?php if($a['inspiracja'] == null) echo "checked"; ?>>Brak</input>-->
        </div>
        <div>
            <input type="radio" name="e_inspiredclient" id="e_inspiredclient1" value=1 <?php if(is_numeric($a['inspiracja'])) print "checked"; ?>></input><label for="e_inspiredclient1">klient</label><br>
            <select name="e_client">
                <?php
                $q = "SELECT id, nazwisko FROM p_klienci WHERE id > 1 ORDER BY id DESC";
                $r = $conn->query($q) or die($conn->error);
                while($b = $r->fetch_assoc()){
                    print "<option value=".$b['id'];
                    if($a['inspiracja'] == $b['id']) print " selected";
                    print ">".$b['nazwisko']."</option>";
                }
                $r->free_result;
                ?>
            </select>
        </div>
    </div>
    <?php if(is_numeric($a['inspiracja'])){ ?>
    <div class='flexright'>
        <h3>Deadline</h3>
        <input type="date" name="e_deadline" <?php if($a['deadline'] != null) echo "value='".$a['deadline']."'"; ?>></input>
        <h3>Wycena</h3>
        <input type="hidden" name="e_nopriceyet" value=<?php echo ($a['cena'] == null) ? 1 : 0; ?>></input>
        <input type="number" name="e_price" <?php if($a['cena'] != null) echo "value=".$a['cena']; ?> step="0.01" min=0></input>
        <h3>Życzenia</h3>
        <textarea name="e_details"><?php if($a['zyczenia'] != null) echo $a['zyczenia']; ?></textarea>
    </div>
    <?php } ?>
    <h3>Data ukończenia</h3>
    <input type="date" name="e_date_out" <?php if($a['data_out'] != null) echo "value='".$a['data_out']."'"; ?>></input>
    <!--<input type="checkbox" name="e_date_out0" <?php if($a['data_out'] == null) echo "checked"; ?>>Brak</input>-->
    <h3>Link</h3>
    <div class="flexright">
        <div>
            <input type="radio" name="e_issafelink" id="e_issafelink0" value=0 <?php if(substr($a['link'], 0, 15) != "http://projects") print "checked"; ?>></input><label for="e_issafelink0">standardowy</label><br>
            <input type="text" name="e_link" <?php if($a['link'] != null) echo "value='".$a['link']."'"; ?> onchange="am_untick('e_link0', this.value);"></input>
            <!--<input type="checkbox" name="e_link0" <?php if($a['link'] == null) echo "checked"; ?>>Brak</input><br>-->
        </div>
        <div>
            <input type="radio" name="e_issafelink" id="e_issafelink1" value=1 <?php if(substr($a['link'], 0, 15) == "http://projects") print "checked"; ?>></input><label for="e_issafelink1">do sejfu</label><br>
        </div>
    </div>
    <input type="hidden" name="e_id" value="<?php echo $a['id']; ?>"></input>
    <?php /*<input type="hidden" name="e_clps" value="<?php echo $a['hasło']; ?>"></input> */ ?>
    <input type="submit" name="e_submit" value="Zatwierdź"></input>
</form>

<b><a href='archmage.php?c=clients'>Wróć do szpicy</a></b>
<?php
    }
}


if(isset($_GET['c'])){
    #################################### tryb klientów ###############################
    //Zebranie potrzebnych danych
    //Klienci
    $q = "SELECT * FROM p_klienci WHERE id <> 1 ORDER BY id DESC";
    $r = $conn->query($q) or die($conn->error);
    while($a = $r->fetch_assoc()){
        $whichclient = ($a['kio'] <= 0) ? "klient" : "deadklient";
        
        $$whichclient[$a['id']]['nazwisko'] = $a['nazwisko'];
        $$whichclient[$a['id']]['haslo'] = $a['hasło'];
        $$whichclient[$a['id']]['mail'] = $a['kontakt'];
        $$whichclient[$a['id']]['tel'] = substr($a['tel'],0,3)." ".substr($a['tel'],3,3)." ".substr($a['tel'],6,3);
        $$whichclient[$a['id']]['kio'] = $a['kio'];
        $$whichclient[$a['id']]['budget'] = $a['budget'];
        $$whichclient[$a['id']]['special'] = $a['special'];
        $$whichclient[$a['id']]['t_mail'] = $a['t_kontakt'];
        $$whichclient[$a['id']]['t_tel'] = substr($a['t_tel'],0,3)." ".substr($a['t_tel'],3,3)." ".substr($a['t_tel'],6,3);
    }
    $r->free_result;
    
    //inicjuję tabele
    $finance = ['accepted' => 0, 'total' => 0];
    
    //projekty
    $filter = (isset($_GET['highlight'])) ? "q.klient_id = ".$_GET['highlight'] : "p.status <> 2";
    $q = "SELECT q.id, p.nazwa, p.status, p.tytuł, q.klient_id, q.data_1, q.deadline, q.data_2, q.data_3, q.data_4, q.data_5, p.link, q.cena
        FROM p_questy q
            LEFT JOIN p_projekty p ON q.id = p.id
        WHERE $filter
        ORDER BY q.data_1 DESC, q.id DESC";
    $r = $conn->query($q) or die($conn->error);
    while($a = $r->fetch_assoc()){
        $whichquest = "quest";
        if($a['status'] != 2){
            if($a['data_5'] == null) $whichquest = "unpaidquest";
            if($a['data_4'] == null) $whichquest = "currentquest";
            if($a['data_2'] == null) $whichquest = "remainingquest";
        }
        
        $$whichquest[$a['id']]['nazwa'] = $a['nazwa'];
        $$whichquest[$a['id']]['tytuł'] = $a['tytuł'];
        $$whichquest[$a['id']]['klient'] = $a['klient_id'];
        $$whichquest[$a['id']]['status'] = $a['status'];
        $$whichquest[$a['id']]['data_1'] = date_create($a['data_1']);
        for($j = 2; $j<=5; $j++){ $$whichquest[$a['id']]['data_'.$j] = ($a['data_'.$j] == null) ? "<a href='?c=tick&cp=".$a['id']."&cd=".$j."' title='Odbij z dzisiejszą datą'>–</a>" : date_create($a['data_'.$j]); }
        $$whichquest[$a['id']]['link'] = $a['link'];
        $$whichquest[$a['id']]['cena'] = $a['cena'];
        $$whichquest[$a['id']]['deadline'] = ($a['deadline'] == null || $a['data_2'] != null) ? "–" : date_create($a['deadline']);
        
        //tabele poboczne
        //„top 5” – zliczacz doświadczenia projektowego
        if($a['status'] == 0) $top5[$a['klient_id']]++; //ukończone projekty -- główny sort
        else if($a['status'] != 2) $top5a[$a['klient_id']]++; //pozostałe projekty
        
        //gus -- przestarzały
        //$gus[$a['data_1']]++;
        
        //ile jeszcze dostanę
        if($a['data_5'] == null && $a['status'] != 2){
            $finance['total'] += $a['cena'];
            if($a['data_4'] != null) $finance['accepted'] += $a['cena'];
        }
    }
    $r->free_result;

    //dopisz doświadczenie do klientów
    $maxexp = 0;
    foreach($klient as $id => $cont){
        $klient[$id]['exp'] = $top5[$id];
        $klient[$id]['exp2'] = $top5a[$id];
        //ustal ile jest najwięcej projektów celem narysowania pasków
        if($top5[$id] > $maxexp) $maxexp = $top5[$id];
        if($klient[$id]['exp'] > $_OPTIONS['veteranlevel']) $veterancount++;
        
        if(max($top5[$id], $top5a[$id]) == 0) $silentklientcount++; //jeżeli nie ma żadnych projektów
    }
    
    //sortuj klientów według doświadczenia
    uasort($klient, function($a,$b){
        $diff = $b['exp'] - $a['exp'];
        if($diff == 0) return ord(substr($a['nazwisko'], 0, 1)) - ord(substr($b['nazwisko'], 0, 1)); //gdyby było ex aequo, patrz na nazwisko
        else return $b['exp'] - $a['exp'];
    });
    
    //średni czas wykonania zlecenia
    // $q = "SELECT avg(datediff(`data_2`, `data_1`)) FROM `p_questy` WHERE `data_4` is not null";
    // $r = $conn->query($q) or die($conn->error);
    // $a = $r->fetch_array();
    // $r->free_result();
    // $avgtimedone = $a[0];

    //heraldic text -- potwierdzenie modyfikowania bazy
    echo $heraldictext;
?>

<?php kalendarz(); ?>

<div class=flexright><div>
    
<div class='archmagetitle'>
<h2>Questy</h2>
<?php
$clearthepast = [];
function printQuest($id, $cont){
    global $klient, $_OPTIONS, $clearthepast;
    $today = date_create("today");
    
    //przygotowanie pasków egzekucyjnych
    $data[0] = date_diff($today, $cont['data_1'])->format('%a');
    for($j = 2; $j<=5; $j++){ $data[$j-1] = (gettype($cont['data_'.$j]) != 'object') ? "null" : date_diff($today, $cont['data_'.$j])->format('%a'); }
    
    //auto-czyszczenie przeszłości
    if(min($data) >= $_OPTIONS['deadline'] && ($cont['link'] !== null && substr($cont['link'], 0, 7) !== "<iframe") && $data[3] != "null" && $data[4] != "null"){
        $clearthepast[] = $id;
        //print "<script>window.location = 'http://projects.wpww.pl/archmage.php?c=clear&cp=$id';</script>";
    }

    //przypuszczacz mailowy
    $mailassume = null;
    if($data[1] == "null" && min($data) == 0){ $mailassume = 1; } //ledwo dodany -- przypuszczam "dodany"
    else if($data[1] != "null" && $data[2] == "null" && min($data) == 0){ $mailassume = 3; } //ledwo zrobiony -- przypuszczam "ocena"
    else if($data[2] != "null" && $data[3] == "null" && min($data) < $_OPTIONS['deadline']*0.8){ $mailassume = 3.5; } //recenzja wydana -- przypuszczam "ocena korekty"
    else if($data[1] != "null" && $data[3] == "null" && min($data) >= $_OPTIONS['deadline']*0.8){ $mailassume = 3.9; } //niewiele do końca i brak oceny -- przypuszczam "odpowiedz!"
    else if($data[4] != "null" && min($data) == 0){ $mailassume = 4; } //wpadły pieniądze -- przypuszczam "opłacony"
    else if($data[3] != "null" && $data[4] == "null" && min($data) >= $_OPTIONS['deadline']*0.8){ $mailassume = 4.9; } //pieniądze jeszcze nie wpadły -- przypuszczam "oddaj pieniądze!"
    if($mailassume !== null) $mailassume = "&mode=".$mailassume;
    
    //kiedy rysować pasek?
    $rysujpasek = false;
    if((min($data) < $_OPTIONS['deadline'] || $data[1] == "null" || $data[2] == "null" || $data[3] == "null" || $data[4] == "null") && $cont['status'] != 2){
        $rysujpasek = true;
        if($data[1] == "null"){ $color = "#ffaaff"; $dcolor = "#dbd";  $desc = "zmagam się z tworzywem"; }
        else if($data[2] == "null"){ $color = "#ff6666"; $dcolor = "#d99"; $desc = "czekam na recenzję"; }
        else if($data[3] == "null"){ $color = "#aaaaaa"; $dcolor = "#999"; $desc = "walczę o jakość"; }
        else if($data[4] == "null"){ $color = "#ffdd66"; $dcolor = "#dc8"; $desc = "czekam na pieniądze"; }
        else{ $color = "#66ff66"; $dcolor = "#9d9"; $desc = "czekam na usunięcie folderu"; }
        $tcolor = $color."44";
        $desc .= " od ostatnich ".min($data)." dni";
        
        //$count = (min($data) >= $_OPTIONS['deadline']) ? $_OPTIONS['deadline'] : min($data); //wariant dla niemodulujących długości pasków, na dole zamiast min($data) ma być wtedy $count
        $barsize = (((((min($data)-1) % $_OPTIONS['deadline'])+1)*100)/$_OPTIONS['deadline'])."%"; //-1+1, bo chcę, żeby %7 = 7, a nie 0, w ogóle -1%7 w php to -1, a nie 6
    }
    
    print "<tr>";
        print "<td class='questdetailstrigger'";
            if(substr($cont['link'], 0, 7) == "<iframe") print "style='color: gold;' title='Projekt ma showcase' ";
            if($cont['status'] == 2) print "style='color: red;' title='Projekt scrappnięty' ";
        print ">";
        print "<div class='questdetails'><a href='http://projects.wpww.pl/?p=$id'>$id ".$cont['nazwa']."</a>";
        if($cont['link'] !== null){
            print "<a href='".$cont['link']."' title='Link do folderu' target='_blank'><strong>";
            if($cont['tytuł'] === null) print "<i>bez tytułu</i>"; else print $cont['tytuł'];
            print "</strong></a>";
        }else{
            print "<strong>";
            if($cont['tytuł'] === null) print "<i>bez tytułu</i>"; else print $cont['tytuł'];
            print "</strong>";
        }
        print "</div>".$id."</td>";
        
        print "<td class='questlistname' colspan=5 ";
            print ($rysujpasek) ? "title='$desc' style='background: linear-gradient(to right, $dcolor 0%, $color $barsize, $tcolor $barsize, transparent 100%); box-shadow: -2px 0 6px #00000077; " : "style='";
            if(strlen($klient[$cont['klient']]['nazwisko'])>$_OPTIONS['longname']) print "font-size: 0.".$_OPTIONS['longnamesize']."em;";
            if($barsize == "100%") print " color: white;";
        print "'>";
            if(isset($_GET['highlight'])){
                print $cont['tytuł'];
            }else{
                if(strpos($klient[$cont['klient']]['mail'], "@")){
                    print "<a href='/mailer.php?id=$id$mailassume' title='Napisz maila a propos projektu'>".$klient[$cont['klient']]['nazwisko']."</a>";
                }else print $klient[$cont['klient']]['nazwisko'];
            }
        /*część o deadline'ie*/
        $deadline = ($cont['deadline'] == "–") ? "" : date_diff($today, $cont['deadline'])->format('%r%a');
        $overtime = ($deadline <= 0);
        if($deadline != ""){ $deadline = "($deadline)"; }
        print "<span style='font-weight: bold; margin-left:8px;";
            if($overtime) print " color: red;";
        print "'>$deadline</span>";
        
        print "</td>";
        
        print "</tr><tr>";
        
        for($j = 1; $j<=5; $j++){
            print "<td>";
            if(gettype($cont['data_'.$j]) == "object") print $cont['data_'.$j]->format("j.m");
                else print $cont['data_'.$j];
            print "</td>";
        }
        
        print "<td";
            if($data[4] == "null") print " style='color: #f6d366;'";
                else if($data[3] != "null") print " style='color: #f6d36644;'";
        print ">".$cont['cena']."</td>";
    print "</tr>";
}
?>
<table class='archmagetable bordered'>
    <tr>
        <td style='background-color: #f6d366;' title='oczekujące'><?php echo count($remainingquest); ?></td>
        <td style='background-color: #f6d36677;' title='niezatwierdzone'><?php echo count($currentquest); ?></td>
        <td style='background-color: #f6d36644;' title='zatwierdzone nieopłacone'><?php echo count($unpaidquest); ?></td>
        <td title='wszystkie'><?php echo (count($quest)+count($remainingquest)+count($currentquest)+count($unpaidquest)); ?></td>
    </tr>
</table>
</div>
<table class='archquestlist'>
    <tr><th>Projekt</th><th colspan=5><?php echo (isset($_GET['highlight'])) ? "Tytuł":"Klient"; ?></th></tr>
    <tr><th>In</th><th>Out</th><th>Rev</th><th>Acc</th><th>Paid</th><th>$</th></tr>
<?php
    foreach([$remainingquest, $currentquest, $unpaidquest, $quest] as $whichquest){
        foreach($whichquest as $id => $cont){
            printQuest($id, $cont);
            
            $printingquestno++;
            if($printingquestno == $_OPTIONS['questlistlength']) break 2;
        }
        if(count($whichquest) != 0) print "<tr class='separatorline'></tr>";
    }
    if(count($clearthepast) != 0) print "<script>window.location = 'http://projects.wpww.pl/archmage.php?c=clear&cp=".implode(",", $clearthepast)."';</script>";
?>
</table>

</div><div>
    
<div class='archmagetitle'>
<h2>Klienci</h2>
<table class='archmagetable bordered'>
<tr>
    <td style='background-color: #f6d366;' title='weterani'><?php echo $veterancount; ?></td>
    <td title='aktywni'><?php echo count($klient)-$kiocount-$silentklientcount; ?></td>
    <td style='background-color: #00000044;' title='nieaktywni'><?php echo $silentklientcount; ?></td>
    <td style='background-color: #FF000044;' title='krętacze i oszuści'><?php echo count($deadklient); ?></td>
</tr>
</table>
</div>
<table style="overflow-y: scroll; height: 45vh; display: block;">
    <tr><form method=post>
        <td colspan=5><input type=text name='c_name' placeholder='[Imię i nazwisko] <[email]> ([telefon]) {[pref.kont.]}' style='width: 100%;'></input></td>
        <td><input type='submit' name='c_submit' value='+' title="Dodaj klienta"></input></td>
    </form></tr>
<?php
    foreach(array($klient, $deadklient) as $z){ foreach($z as $x => $y){
        //obliczenia procentowe na potrzeby pasków doświadczenia klientów
        $barsize = ($y['exp'] > $_OPTIONS['veteranlevel']) ? ($y['exp']/$maxexp)*100 : ($y['exp']/($_OPTIONS['veteranlevel'] + 1))*100;
        $barsize_new = ($y['exp'] != 0) ? $barsize/$y['exp']*$y['exp2'] + $barsize : ($y['exp2']/($_OPTIONS['veteranlevel'] + 1))*100;
            $barsize .= "%"; $barsize_new .= "%";
        
        print "<tr>";
            //doświadczenie
            print "<td title='Doświadczenie projektowe'>".$y['exp'];
                if($y['exp2'] != 0) print "<span style='color: #f6d366; text-shadow: 0 0 3px #000000aa'>+".$y['exp2']."</span>";
            print "</td>";
            //nazwisko
            print "<td style='";
                if($y['kio'] ==  1) print "color: red; ";
            print "background: linear-gradient(to right, ";
                print ($y['exp'] > $_OPTIONS['veteranlevel']) ?
                    "#f6d366 0%, #c494e3 $barsize, #f6d36677 $barsize, #f6d366 $barsize_new, #f6d36677 $barsize_new" :
                    "#926fa8 0%, #c494e3 $barsize, #f6d366 $barsize, #f6d366 $barsize_new, #f6d36677 $barsize_new";
            print ", transparent 100%); box-shadow: -2px 0 6px #00000077;";
                if(strlen($y['nazwisko'])>$_OPTIONS['longname']) print " font-size: 0.".$_OPTIONS['longnamesize']."em;";
            print "'><a href='?e=new&whose=$x' title='Dodaj nowy projekt na to nazwisko'>".$y['nazwisko']."</a>";
            //etykiety nazwiska
                if($y['special']) print "<span style='color: gold; user-select: none;' title='Specjalne traktowanie'>♦</span>";
                if($y['kio'] == -1) print "<span style='color: lime; user-select: none;' title='Nadzwyczajne zaufanie'>♥</span>";
                if($y['budget'] > 0) print "<span style='color: cyan; user-select: none;' title='Posiada budżet w wysokości ".$y['budget']." zł'>♠</span>";
            print "</td>";
            //hasło
            print "<td style='font-size: 6px' title='Hash'>".$y['haslo']."</td>";
                if($y['t_tel'] != null) $y['tel'] = $y['tel']."<br>".$y['t_tel'];
            //telefon
            print "<td style='font-size: 9px' title='Telefon'>".$y['tel']."</td>";
            //mail
            print "<td>";
                if($y['t_mail'] != null) $y['mail'] = $y['mail'].";".$y['t_mail'];
                if(strpos($y['mail'], "@") !== false) print "<a href='mailto:".$y['mail']."?subject=[WPWW] ' title='".$y['mail']."'>@</a>";
                if(!strpos($y['mail'], "@") && $y['mail'] !== null) print "<span style='user-select: none; font-weight: bold; color: #0099FF;' title='".$y['mail']."'>".substr($y['mail'], 0, 1)."</span>";
            print "</td>";
            //guziki
            print "<td style='white-space: nowrap;'>";
                print "<span class='hoverbutton green' title='Wyświetl jego projekty'><a target= '_blank' href='?c=clients&highlight=".$x."'>?</a></span>";
                print "<span class='hoverbutton yellow' title='Wyceń projekt'><a href='wyceniacz.php?name=".substr($y['nazwisko'], 0, strpos($y['nazwisko'], " "));
                    if($y['exp'] > $_OPTIONS['veteranlevel']) print "&vet=1";
                    print "'>$</a></span>";
                // print ($y['kio'] < 1) ? "<span class='hoverbutton red' title='Oznacz jako krętacza i oszusta'><a href='?kio=$x'>!</a></span>"
                //                     : "<span class='hoverbutton green' title='Przywróć zaufanie'><a href='?jcu=$x'>♥</a></span>";
            print "</td>";
        print "</tr>";
    }}
?>
</table>
<p><b><a href="wyceniacz.php">Wyceń kompletnie nowy projekt</a></b></p>
<!--<h4>średni czas realizacji (dni): <?php echo $avgtimedone; ?></h4>-->

<div class='archmagetitle'>
<h2>Do zapłacenia</h2>
<table class='archmagetable bordered'>
<tr>
    <td style='background-color: #f6d366;' title='zatwierdzone'><?php echo $finance['accepted']." zł"; ?></td>
    <td title='łącznie'><?php echo $finance['total']." zł"; ?></td>
</tr>
</table>
</div>
    
</div>
<!--
<div>
<h2>GUS</h2>
<?php
    /*
    $currentday = date_create();
    $thelastday = date_create();
    date_sub($thelastday, date_interval_create_from_date_string('30 days')); //30 dni wstecz
    
    do{
        $gus2[$currentday->format("j.m")] = (isset($gus[$currentday->format("Y-m-d")])) ? $gus[$currentday->format("Y-m-d")] : 0;
        $isitweekend[$currentday->format("j.m")] = ($currentday->format("N") > 5) ? 1 : 0;
        date_sub($currentday, date_interval_create_from_date_string('1 day'));
    }while($currentday->format("Y-m-d") != $thelastday->format("Y-m-d"));
    
    //przygotuj paski
    $maxcount = max($gus2); //największa wartość paska
    print "<table class='archstats' style='overflow-y: scroll; height: 55vh; display: block;'>";
    foreach($gus2 as $data => $count){
        print "<tr>";
        print "<td";
            if($isitweekend[$data]) print " style='color: #f6d366'";
        print ">$data</td>";
        print "<td><div class='databar' style='width: ".(($count/$maxcount)*150)."px;'></div>$count</td>";
        print "</tr>";
        $howmanyintotal += $count;
    }
    print "</table>";
    print "Miesięcznie <b>$howmanyintotal</b> zleceń.";
    */
?>

</div>
-->
</div>

<?php } ?>

</div>

<br><a href="/"><b>Wyjdź ze szpicy</b></a>

<?
generateBottom("main");
?>